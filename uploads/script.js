$(document).ready(function() {
    function cargarUsuarios() {
        $.ajax({
            url: 'get_usuarios.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#lista-usuarios').empty();
                response.usuarios.forEach(usuario => {
                    $('#lista-usuarios').append(
                        `<li data-id="${usuario.id_usuario}" class="usuario">${usuario.nombre}</li>`
                    );
                });
            }
        });
    }

    function cargarMensajes(id_destinatario) {
        $.ajax({
            url: 'mostrar_mensaje.php',
            method: 'GET',
            data: { id_destinatario },
            dataType: 'json',
            success: function(response) {
                $('#mensajes').empty();
                response.mensajes.forEach(mensaje => {
                    $('#mensajes').append(`<div><strong>${mensaje.remitente}:</strong> ${mensaje.contenido}</div>`);
                });
            }
        });
    }

    $(document).on('click', '.usuario', function() {
        var id_destinatario = $(this).data('id');
        $('#id_destinatario').val(id_destinatario);
        cargarMensajes(id_destinatario);
    });

    $('#form-mensaje').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'enviar_mensaje.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function() {
                cargarMensajes($('#id_destinatario').val());
                $('#contenido').val('');
            }
        });
    });

    setInterval(() => {
        var id_destinatario = $('#id_destinatario').val();
        if (id_destinatario) cargarMensajes(id_destinatario);
    }, 5000);

    cargarUsuarios();
});
