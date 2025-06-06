// ======= Налаштування змінних =======
let selectedOverlay = null;
const snapBtn = document.getElementById('snap');
const overlayList = document.getElementById('overlay-list');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const upload = document.getElementById('upload');
const preview = document.getElementById('preview');
const saveForm = document.getElementById('save-form');
const photoData = document.getElementById('photo-data');
const overlayData = document.getElementById('overlay-data');

// ======= Вибір накладки =======
if (overlayList) {
    overlayList.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG') {
            selectedOverlay = e.target.getAttribute('data-overlay');
            snapBtn.disabled = false;
            // Підсвітити вибрану накладку
            Array.from(overlayList.children).forEach(img => img.classList.remove('selected'));
            e.target.classList.add('selected');
        }
    });
}

// ======= Веб-камера =======
if (video && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            video.srcObject = stream;
            video.play();
        })
        .catch(function() {
            if (document.getElementById('camera-area'))
                document.getElementById('camera-area').innerHTML = "<b>Веб-камера недоступна. Використайте завантаження файлу.</b>";
        });
}

// ======= Знімок з веб-камери =======
if (snapBtn) {
    snapBtn.addEventListener('click', function() {
        if (!selectedOverlay) return;
        canvas.style.display = 'block';
        preview.style.display = 'none';
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        // Додаємо накладку для попереднього перегляду
        let overlayImg = new window.Image();
        overlayImg.src = 'public/overlays/' + selectedOverlay;
        overlayImg.onload = function() {
            canvas.getContext('2d').drawImage(overlayImg, 0, 0, canvas.width, canvas.height);
            saveForm.style.display = 'block';
            photoData.value = canvas.toDataURL('image/png');
            overlayData.value = selectedOverlay;
        }
    });
}

// ======= Завантаження файлу =======
if (upload) {
    upload.addEventListener('change', function(e) {
        let file = e.target.files[0];
        if (!file) return;
        let reader = new FileReader();
        reader.onload = function(ev) {
            preview.src = ev.target.result;
            preview.style.display = 'block';
            if (video) video.style.display = 'none';
            canvas.style.display = 'none';
            snapBtn.disabled = !selectedOverlay;
            snapBtn.onclick = function() {
                let img = new window.Image();
                img.src = preview.src;
                img.onload = function() {
                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                    let overlayImg = new window.Image();
                    overlayImg.src = 'public/overlays/' + selectedOverlay;
                    overlayImg.onload = function() {
                        canvas.getContext('2d').drawImage(overlayImg, 0, 0, canvas.width, canvas.height);
                        canvas.style.display = 'block';
                        saveForm.style.display = 'block';
                        photoData.value = canvas.toDataURL('image/png');
                        overlayData.value = selectedOverlay;
                    }
                }
            }
        }
        reader.readAsDataURL(file);
    });
}
