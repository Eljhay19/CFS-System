(() => {
    const input = document.querySelector('[data-logo-input]');
    const dropArea = document.querySelector('[data-logo-drop]');
    const preview = document.querySelector('[data-logo-preview]');
    const chooseButton = document.querySelector('[data-logo-choose]');
    if (!input || !dropArea || !preview || input.matches(':disabled')) return;
    let imageUrl;
    chooseButton?.addEventListener('click', () => input.click());
    const previewFile = (file) => {
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type)) return;
        if (file.size > 2 * 1024 * 1024) return;
        if (imageUrl) URL.revokeObjectURL(imageUrl);
        imageUrl = URL.createObjectURL(file);
        const image = document.createElement('img');
        image.src = imageUrl;
        image.alt = 'Selected business logo';
        preview.replaceChildren(image);
    };
    input.addEventListener('change', () => previewFile(input.files[0]));
    ['dragenter', 'dragover'].forEach((name) => dropArea.addEventListener(name, (event) => {
        event.preventDefault();
        dropArea.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach((name) => dropArea.addEventListener(name, (event) => {
        event.preventDefault();
        dropArea.classList.remove('is-dragging');
    }));
    dropArea.addEventListener('drop', (event) => {
        const file = event.dataTransfer?.files[0];
        if (!file || !['image/jpeg', 'image/png'].includes(file.type) || file.size > 2 * 1024 * 1024) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        previewFile(file);
    });
})();
