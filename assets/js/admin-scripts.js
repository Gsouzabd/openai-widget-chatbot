jQuery(document).ready(function($) {
    var mediaUploader;

    $('#chatbot_icon_button').click(function(e) {
        e.preventDefault();

        // Se o Media Uploader já foi criado, apenas abre ele
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Cria o Media Uploader
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Escolher Imagem',
            button: {
                text: 'Escolher Imagem'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var imageUrl = attachment.url; // URL da imagem selecionada

            // Atualiza o valor do campo de texto
            $('#chatbot_icon_url').val(imageUrl);

            // Atualiza a imagem exibida em tempo real
            $('#chatbot_icon_preview').attr('src', imageUrl);
        });

        mediaUploader.open();
    });
});
