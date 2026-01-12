<div class="gallery">
    <input type="file" id="uploadInput" multiple>
    <div class="thumbnails" id="thumbnails"></div>
</div>

<script>
    $(document).ready(function() {
        $('#uploadInput').on('change', function() {
            var files = $(this)[0].files;
            var thumbnailsContainer = $('#thumbnails');
            var loader = $('#loader');

            thumbnailsContainer.empty();
            loader.show();

            for (var i = 0; i < files.length; i++) {
                var file = files[i];

                if (file.type.match('image.*')) {
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        var thumbnail = '<div class="thumbnail">' +
                            '<img src="' + e.target.result + '">' +
                            '<div class="close-button">×</div>' +
                            '</div>';
                        thumbnailsContainer.append(thumbnail);
                    }

                    reader.readAsDataURL(file);
                }
            }

            loader.hide();
        });

        $('#thumbnails').on('click', '.close-button', function() {
            $(this).parent('.thumbnail').remove();
        });

        $('#uploadInput').on('click', function() {
            $(this).val('');
        });
    });
</script>