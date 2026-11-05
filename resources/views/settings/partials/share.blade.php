<section class="settings-section" aria-labelledby="share-heading">
    <h2 id="share-heading"><x-icon name="link" />Share Survey</h2>
    <div class="settings-share-content">
        <canvas data-survey-qr data-url="{{ route('survey.public', $business->survey_slug) }}" aria-label="Survey QR code" role="img"></canvas>
        <div><h3>Survey QR Code</h3><p>Customer survey link</p><div class="settings-share-actions"><button class="button compact-button settings-secondary" type="button" data-copy-survey><x-icon name="link" />Copy Link</button><a class="button compact-button settings-secondary" data-download-qr download="cfs-survey-qr.png"><x-icon name="download" />Download QR</a></div></div>
    </div>
    <p class="settings-copy-status" role="status" data-copy-status></p>
</section>
