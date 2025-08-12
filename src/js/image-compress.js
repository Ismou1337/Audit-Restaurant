// This file handles image compression on the client side before uploading.

document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('imageInput');
    const compressButton = document.getElementById('compressButton');

    compressButton.addEventListener('click', function() {
        if (imageInput.files.length === 0) {
            alert('Veuillez sélectionner une image à compresser.');
            return;
        }

        const file = imageInput.files[0];
        const reader = new FileReader();

        reader.onload = function(event) {
            const img = new Image();
            img.src = event.target.result;

            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                const MAX_WIDTH = 800; // Set the maximum width
                const MAX_HEIGHT = 800; // Set the maximum height
                let width = img.width;
                let height = img.height;

                // Calculate the new dimensions
                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                // Draw the image on the canvas
                ctx.drawImage(img, 0, 0, width, height);

                // Compress the image
                canvas.toBlob(function(blob) {
                    const compressedFile = new File([blob], file.name, { type: file.type });
                    uploadImage(compressedFile);
                }, 'image/jpeg', 0.7); // Adjust quality as needed
            };
        };

        reader.readAsDataURL(file);
    });

    function uploadImage(file) {
        const formData = new FormData();
        formData.append('image', file);

        fetch('traitement-simple.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Image téléchargée avec succès !');
            } else {
                alert('Erreur lors du téléchargement de l\'image : ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors du téléchargement de l\'image.');
        });
    }
});