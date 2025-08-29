<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use App\Application\InputData\Import\ImportUploadInputData;
use App\Application\InputData\Import\ImportSearchInputData;
use App\Application\InputData\Import\ImportExecInputData;
use App\Infrastructure\Dao\ImportDao;
use App\Infrastructure\Dao\ContractTypeDao;
use App\Infrastructure\Dao\JoinWayDao;
use App\Infrastructure\Dao\OccupationDao;
use App\Infrastructure\Dao\PositionDao;
use App\Infrastructure\Dao\RoleDao;
use App\Infrastructure\Dao\UserDao;
use App\Infrastructure\Dao\UserDuplicatedDao;
use App\Infrastructure\Dao\UserHistoryDao;
use App\Infrastructure\Dao\InvalidUserDao;

class ImportUserTest extends AbstractTestCase
{
    /**
     * @return void
     */
    public function testInside()
    {
        $time = time();

        $contractTypeId = ContractTypeDao::create([
            'name' => '職員',
        ])->id;

        $joinWayId = JoinWayDao::create([
            'name' => '定時採用',
        ])->id;

        $occupationId = OccupationDao::create([
            'name' => '営業職',
            'search' => '営業職',
            'order' => 1,
        ])->id;

        $positionId = PositionDao::create([
            'name' => '部署長',
        ])->id;

        $roleId = RoleDao::create([
            'name' => 'プロフェッショナル(ﾃﾞｨﾚｸﾀｰ)',
        ])->id;

        UserDao::truncate();

        $this->assertSame(0,ImportDao::count());
        $file = $this->getUploadedFile('User-sample.xlsx');
        $inputData = new ImportUploadInputData([
            'file' => $file,
            'type' => 1,
        ]);
        $this->handleUseCase($inputData);
        $this->assertSame(1,ImportDao::count());


        $inputData = new ImportSearchInputData();
        $outputData = $this->handleUseCase($inputData);
        $this->assertCount(1, $outputData->imports);


        $importId = $outputData->imports[0]->importId;

        $content = <<<EOM
ユーザID,(旧)ユーザID,氏名,氏名（姓）,氏名（名）,カナ氏名（姓）,カナ氏名（名）,アルファベット氏名（姓）,アルファベット氏名（名）,メインメールアドレス,部門グループコード,部門グループ名称,部門コード,部門名称,部署コード,部署名称,役職名称,従業員区分,本務会社名称,本務部門グループコード,本務部門グループ名称,本務部門コード,本務部門名称,本務部署コード,本務部署名称,プロパー／キャリア入社,職種名称,ロール,社会年齢,性別
001,,鈴木 太郎,鈴木,太郎,スズキ,タロウ,Suzuki,Taro,user01@example.com,001,第1カンパニー,001,管理部門,001,経理部署,部署長,職員,ＨＣ,001,博報堂ＤＹホールディングス,001,ＨＤＹＨＣグループ人材開発戦略局,001,ＨＤＹＨＣグループ人材開発戦略局グループ能力開発グループ,定時採用,営業職,プロフェッショナル(ﾃﾞｨﾚｸﾀｰ),33,男性
002,,山田 太郎,山田,太郎,ヤマダ,タロウ,Yamada,Taro,user02@example.com,001,第1カンパニー,001,管理部門,001,経理部署,部署長,職員,ＨＣ,001,博報堂ＤＹホールディングス,001,ＨＤＹＨＣグループ人材開発戦略局,001,ＨＤＹＨＣグループ人材開発戦略局グループ能力開発グループ,定時採用,営業職,プロフェッショナル(ﾃﾞｨﾚｸﾀｰ),33,男性
001,,不正 太郎,不正,太郎,フセイ,タロウ,Fusei,Taro,user02@example.com,001,第1カンパニー,001,管理部門,001,経理部署,部署長,職員,ＨＣ,001,博報堂ＤＹホールディングス,001,ＨＤＹＨＣグループ人材開発戦略局,001,ＨＤＹＨＣグループ人材開発戦略局グループ能力開発グループ,定時採用,営業職,プロフェッショナル(ﾃﾞｨﾚｸﾀｰ),33,男性
EOM;
        ImportDao::where('id', $importId)->update(['content' => $content]);

        $inputData = new ImportExecInputData([
            'importId' => $importId,
        ], $time);
        $outputData = $this->handleUseCase($inputData);

        $this->assertSame(2, ImportDao::value('status'));
        $this->assertSame(2, UserDao::count());
        $this->assertSame(2, UserHistoryDao::count());
        $this->assertSame(0, UserDuplicatedDao::count());
        $this->assertSame(1, InvalidUserDao::count());

        // 鈴木太郎が再雇用
        $content = str_replace('001,,鈴木 太郎', '011,001,鈴木 太郎', $content);
        ImportDao::where('id', $importId)->update(['content' => $content, 'status' => 0]);
        $this->handleUseCase($inputData);

        $this->assertSame(2, ImportDao::value('status'));
        $this->assertSame(2, UserDao::count());
        $this->assertSame(3, UserHistoryDao::count());
        $this->assertSame(0, UserDuplicatedDao::count());
        $this->assertSame(2, InvalidUserDao::count());

        ImportDao::where('id', $importId)->update(['status' => 0]);
        $this->handleUseCase($inputData);

        $this->assertSame(2, ImportDao::value('status'));
        $this->assertSame(2, UserDao::count());
        $this->assertSame(3, UserHistoryDao::count());
        $this->assertSame(0, UserDuplicatedDao::count());
        $this->assertSame(3, InvalidUserDao::count());

        // ここから追加処理
        $userId = UserDao::orderBy('id', 'DESC')->value('id');
        UserDao::where('id', $userId)->update(['employeeCode' => 999]);
        ImportDao::where('id', $importId)->update(['status' => 0]);
        $this->handleUseCase($inputData);

        $this->assertSame(2, ImportDao::value('status'));
        $this->assertSame(2, UserDao::count());
        $this->assertSame(3, UserHistoryDao::count());
        $this->assertSame(1, UserDuplicatedDao::count());
        $this->assertSame(4, InvalidUserDao::count());
    }
}
