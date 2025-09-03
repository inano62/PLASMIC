// helpers/devices.ts などに
export async function pickFrontCameraDeviceId(): Promise<string | undefined> {
    // ラベルを得るために一度だけ権限を取ってすぐ止める（ラベルが空だと判定できない）
    try {
        const tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tmp.getTracks().forEach(t => t.stop());
    } catch (_) {
        // 権限無しでも後続の enumerate は動くので無視
    }

    const devices = await navigator.mediaDevices.enumerateDevices();
    const videos = devices.filter(d => d.kind === 'videoinput');

    // “front / user / FaceTime / 内/前/インカメ” などを優先
    const frontLike = videos.find(d =>
        /front|user|face|内|前|イン|self|facetime/i.test(d.label)
    );

    return frontLike?.deviceId ?? videos[0]?.deviceId;
}
