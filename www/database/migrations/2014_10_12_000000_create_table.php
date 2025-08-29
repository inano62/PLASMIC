<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array
     */
    public function tables()
    {
        return [
            ['sessions', function (Blueprint $table) {
                $table->string('id', 191)->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            }],
            ['CronProcess', function(Blueprint $table) {
                $table->string('name', 32);
                $table->enum('status', ['stopped', 'running'])->default('stopped');
                $table->datetime('begunAt');
                $table->datetime('succeededAt')->nullable();
                $table->datetime('failedAt')->nullable();
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->primary('name');
            }],
            ['Import', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedSmallInteger('status')->default(0)->comment('0:初期,1:処理中,2:成功,3:失敗');
                $table->string('filename', 191);
                $table->string('mimeType', 191);
                $table->binary('content');
                $table->unsignedSmallInteger('type')->comment('1:人事データ,2:サーベイ');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['UserImport', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedSmallInteger('status')->default(0)->comment('0:初期,1:処理中,2:成功,3:失敗');
                $table->string('filename', 191);
                $table->string('mimeType', 64);
                $table->longText('content');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            // 社員情報分類のために必要
            ['ContractType', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 63);
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['JoinWay', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 63);
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['Occupation', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 63);
                $table->string('search', 63);
                $table->unsignedBigInteger('order');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['Position', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 63);
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['Role', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 63);
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['User', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('contractTypeId')->nullable();
                $table->foreignId('joinWayId')->nullable();
                $table->foreignId('occupationId')->nullable();
                $table->foreignId('positionId')->nullable();
                $table->foreignId('roleId')->nullable();
                $table->unsignedBigInteger('employeeCode');
                $table->string('email', 63)->nullable();
                $table->string('displayName', 63)->nullable();
                $table->unsignedSmallInteger('status')->default(0)->comment('0:enabled,1:disabled,2:duplicated');
                $table->text('permissions')->nullable();
                $table->string('surnameKanji', 63)->nullable();
                $table->string('forenameKanji', 63)->nullable();
                $table->string('surnameKatakana', 63)->nullable();
                $table->string('forenameKatakana', 63)->nullable();
                $table->string('surnameAlphabet', 63)->nullable();
                $table->string('forenameAlphabet', 63)->nullable();
                $table->unsignedSmallInteger('gender')->nullable()->comment('1:男性,2:女性');
                $table->unsignedBigInteger('bumonGroupCode')->nullable();
                $table->string('bumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('bumonCode')->nullable();
                $table->string('bumonName', 127)->nullable();
                $table->unsignedBigInteger('bushoCode')->nullable();
                $table->string('bushoName', 127)->nullable();
                $table->string('honmuCompanyName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonGroupCode')->nullable();
                $table->string('honmuBumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonCode')->nullable();
                $table->string('honmuBumonName', 127)->nullable();
                $table->unsignedBigInteger('honmuBushoCode')->nullable();
                $table->string('honmuBushoName', 127)->nullable();
                $table->unsignedInteger('socialAge')->nullable();
                $table->datetime('socialAgeAt')->nullable();
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->index('email');
                $table->unique('employeeCode');
                $table->foreign('contractTypeId')->references('id')->on('ContractType');
                $table->foreign('joinWayId')->references('id')->on('JoinWay');
                $table->foreign('occupationId')->references('id')->on('Occupation');
                $table->foreign('positionId')->references('id')->on('Position');
                $table->foreign('roleId')->references('id')->on('Role');
            }],
            ['UserDuplicated', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('contractTypeId');
                $table->foreignId('joinWayId');
                $table->foreignId('occupationId');
                $table->foreignId('positionId');
                $table->foreignId('roleId');
                $table->unsignedSmallInteger('status')->default(0)->comment('0:untreated,1:approved,2:rejected');
                $table->string('email', 63);
                $table->unsignedBigInteger('employeeCode');
                $table->string('displayName', 63);
                $table->string('surnameKanji', 63)->nullable();
                $table->string('forenameKanji', 63)->nullable();
                $table->string('surnameKatakana', 63)->nullable();
                $table->string('forenameKatakana', 63)->nullable();
                $table->string('surnameAlphabet', 63)->nullable();
                $table->string('forenameAlphabet', 63)->nullable();
                $table->unsignedSmallInteger('gender')->nullable()->comment('1:男性,2:女性');
                $table->unsignedBigInteger('bumonGroupCode')->nullable();
                $table->string('bumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('bumonCode')->nullable();
                $table->string('bumonName', 127)->nullable();
                $table->unsignedBigInteger('bushoCode')->nullable();
                $table->string('bushoName', 127)->nullable();
                $table->string('honmuCompanyName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonGroupCode')->nullable();
                $table->string('honmuBumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonCode')->nullable();
                $table->string('honmuBumonName', 127)->nullable();
                $table->unsignedBigInteger('honmuBushoCode')->nullable();
                $table->string('honmuBushoName', 127)->nullable();
                $table->unsignedInteger('socialAge')->nullable();
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->unique(['email', 'employeeCode']);
                $table->foreign('contractTypeId')->references('id')->on('ContractType');
                $table->foreign('joinWayId')->references('id')->on('JoinWay');
                $table->foreign('occupationId')->references('id')->on('Occupation');
                $table->foreign('positionId')->references('id')->on('Position');
                $table->foreign('roleId')->references('id')->on('Role');
            }],
            ['UserHistory', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('importId')->nullable();
                $table->foreignId('userId');
                $table->foreignId('contractTypeId');
                $table->foreignId('joinWayId');
                $table->foreignId('occupationId');
                $table->foreignId('positionId');
                $table->foreignId('roleId');
                $table->string('email', 63);
                $table->string('displayName', 63);
                $table->unsignedBigInteger('employeeCode')->nullable();
                $table->string('surnameKanji', 63)->nullable();
                $table->string('forenameKanji', 63)->nullable();
                $table->string('surnameKatakana', 63)->nullable();
                $table->string('forenameKatakana', 63)->nullable();
                $table->string('surnameAlphabet', 63)->nullable();
                $table->string('forenameAlphabet', 63)->nullable();
                $table->unsignedSmallInteger('gender')->nullable()->comment('1:男性,2:女性');
                $table->unsignedBigInteger('bumonGroupCode')->nullable();
                $table->string('bumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('bumonCode')->nullable();
                $table->string('bumonName', 127)->nullable();
                $table->unsignedBigInteger('bushoCode')->nullable();
                $table->string('bushoName', 127)->nullable();
                $table->string('honmuCompanyName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonGroupCode')->nullable();
                $table->string('honmuBumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonCode')->nullable();
                $table->string('honmuBumonName', 127)->nullable();
                $table->unsignedBigInteger('honmuBushoCode')->nullable();
                $table->string('honmuBushoName', 127)->nullable();
                $table->unsignedInteger('socialAge')->nullable();
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->foreign('userId')->references('id')->on('User');
                $table->foreign('importId')->references('id')->on('Import');
                $table->foreign('contractTypeId')->references('id')->on('ContractType');
                $table->foreign('joinWayId')->references('id')->on('JoinWay');
                $table->foreign('occupationId')->references('id')->on('Occupation');
                $table->foreign('positionId')->references('id')->on('Position');
                $table->foreign('roleId')->references('id')->on('Role');
            }],
            ['InvalidUser', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('contractTypeId');
                $table->foreignId('joinWayId');
                $table->foreignId('occupationId');
                $table->foreignId('positionId');
                $table->foreignId('roleId');
                $table->string('email', 63)->nullable();
                $table->string('displayName', 63)->nullable();
                $table->unsignedBigInteger('employeeCode')->nullable();
                $table->string('surnameKanji', 63)->nullable();
                $table->string('forenameKanji', 63)->nullable();
                $table->string('surnameKatakana', 63)->nullable();
                $table->string('forenameKatakana', 63)->nullable();
                $table->string('surnameAlphabet', 63)->nullable();
                $table->string('forenameAlphabet', 63)->nullable();
                $table->unsignedSmallInteger('gender')->nullable()->comment('1:男性,2:女性');
                $table->unsignedBigInteger('bumonGroupCode')->nullable();
                $table->string('bumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('bumonCode')->nullable();
                $table->string('bumonName', 127)->nullable();
                $table->unsignedBigInteger('bushoCode')->nullable();
                $table->string('bushoName', 127)->nullable();
                $table->string('honmuCompanyName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonGroupCode')->nullable();
                $table->string('honmuBumonGroupName', 127)->nullable();
                $table->unsignedBigInteger('honmuBumonCode')->nullable();
                $table->string('honmuBumonName', 127)->nullable();
                $table->unsignedBigInteger('honmuBushoCode')->nullable();
                $table->string('honmuBushoName', 127)->nullable();
                $table->unsignedInteger('socialAge')->nullable();
                $table->datetime('socialAgeAt')->nullable();
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->foreign('contractTypeId')->references('id')->on('ContractType');
                $table->foreign('joinWayId')->references('id')->on('JoinWay');
                $table->foreign('occupationId')->references('id')->on('Occupation');
                $table->foreign('positionId')->references('id')->on('Position');
                $table->foreign('roleId')->references('id')->on('Role');
            }],
            ['UserToken', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('userId');
                $table->unsignedSmallInteger('type')->comment('1:unique');
                $table->string('codehash', 63);
                $table->datetime('expiredAt');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->index('codehash');
                $table->index('expiredAt');
                $table->foreign('userId')->references('id')->on('User');
            }],
            ['SurveyCampaign', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 191);
                $table->date('dateFrom');
                $table->date('dateTo');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['SurveyAnswer', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('surveyCampaignId');
                $table->foreignId('userId');
                $table->unsignedBigInteger('uid');
                $table->datetime('at');
                $table->unsignedSmallInteger('quadrant')->nullable()->comment('20象限');
                // 必要な文だけ追加
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->foreign('surveyCampaignId')->references('id')->on('SurveyCampaign');
                $table->foreign('userId')->references('id')->on('User');
            }],
            ['NotifyHistory', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('userId')->nullable();
                $table->string('trigger', 31);
                $table->string('to', 255);
                $table->string('cc', 255)->nullable();
                $table->string('subject', 255);
                $table->text('body');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->foreign('userId')->references('id')->on('User');
            }],
            ['Segmentset', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 191);
                $table->unsignedInteger('order');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
            ['Segment', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('segmentsetId');
                $table->unsignedInteger('type');
                $table->unsignedInteger('color');
                $table->double('value01')->nullable(); // 2022など
                $table->unsignedInteger('order');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
                $table->foreign('segmentsetId')->references('id')->on('Segmentset');
            }],
            ['Downloadable', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 191);
                $table->string('mimeType', 64);
                $table->binary('content');
                $table->datetime('createdAt')->useCurrent();
                $table->datetime('updatedAt')->useCurrent();
            }],
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables() as $a) {
            if (!Schema::hasTable($a[0])) { Schema::create($a[0], $a[1]); }
        }

        $prefix = \DB::getTablePrefix();
        \DB::statement("ALTER TABLE `${prefix}Import` MODIFY `content` LONGBLOB");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (array_reverse($this->tables()) as $a) {
            Schema::dropIfExists($a[0]);
        }
    }
};
