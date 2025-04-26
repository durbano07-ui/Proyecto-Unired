// Función para mostrar/ocultar el menú de opciones
function toggleOpciones(postId) {
    const opcionesLista = document.getElementById(`opciones-${postId}`);
    if (opcionesLista.style.display === 'none') {
        opcionesLista.style.display = 'block';
    } else {
        opcionesLista.style.display = 'none';
    }
}

// Función para dar/quitar like
function toggleLike(postId, button) {
    // Enviar solicitud AJAX para dar/quitar like
    fetch('like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ postId: postId })
    })
    .then(response => {
        if (response.ok) {
            // Cambiar estado visual del botón
            button.classList.toggle('liked');
            
            // Cambiar color del icono
            const icon = button.querySelector('i');
            if (button.classList.contains('liked')) {
                icon.style.color = '#e74c3c'; // Rojo cuando está liked
            } else {
                icon.style.color = '#fff'; // Blanco cuando no está liked
            }
            
            // Actualizar contador
            const likesCount = document.getElementById(`likes-count-${postId}`);
            let count = parseInt(likesCount.textContent);
            
            if (button.classList.contains('liked')) {
                likesCount.textContent = count + 1;
            } else {
                likesCount.textContent = Math.max(0, count - 1);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// Función para mostrar/ocultar caja de comentarios
function toggleCommentBox(postId) {
    const commentBox = document.getElementById(`comment-box-${postId}`);
    if (commentBox.style.display === 'none' || commentBox.style.display === '') {
        commentBox.style.display = 'block';
        // Enfocar el campo de texto
        document.getElementById(`comment-input-${postId}`).focus();
    } else {
        commentBox.style.display = 'none';
    }
}

// Función para enviar comentario
function submitComment(postId) {
    const commentInput = document.getElementById(`comment-input-${postId}`);
    const comentario = commentInput.value.trim();
    
    if (comentario === '') {
        alert('El comentario no puede estar vacío');
        return;
    }
    
    // Enviar solicitud AJAX para guardar el comentario
    fetch('comentar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: postId,
            comentario: comentario
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Crear el nuevo comentario en la interfaz
            const commentsContainer = document.getElementById(`comments_${postId}`);
            
            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            commentElement.id = `comment_${data.comment_id}`;
            
            commentElement.innerHTML = `
                <div class='comment-header'>
                    <strong>${data.nombre}</strong> <small>${data.fecha}</small>
                </div>
                <p>${data.contenido}</p>
            `;
            
            // Agregar el comentario al inicio del contenedor
            commentsContainer.prepend(commentElement);
            
            // Limpiar el campo de entrada
            commentInput.value = '';
            
            // Opcionalmente, ocultar la caja de comentarios
            // toggleCommentBox(postId);
        } else {
            alert(data.message || 'Error al comentar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar el comentario');
    });
}

// Función para compartir publicación
function sharePost(postId) {
    // Crear la URL de la publicación
    const postUrl = `${window.location.origin}/detalle.php?id=${postId}`;
    
    // Comprobar si el navegador soporta la API de compartir
    if (navigator.share) {
        navigator.share({
            title: 'Publicación compartida',
            url: postUrl
        })
        .catch(err => {
            console.error('Error al compartir:', err);
            // Fallback: mostrar diálogo para copiar enlace
            prompt('Copia este enlace para compartir la publicación:', postUrl);
        });
    } else {
        // Fallback para navegadores que no soportan la API de compartir
        prompt('Copia este enlace para compartir la publicación:', postUrl);
    }
}

// Función para eliminar publicación
function eliminarPublicacion(postId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta publicación?')) {
        // Crear FormData para enviar los datos
        const formData = new FormData();
        formData.append('id', postId);
        
        // Enviar solicitud AJAX
        fetch('eliminar_publicacion.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminar la publicación de la interfaz
                const post = document.getElementById(`post_${postId}`);
                post.remove();
            } else {
                alert(data.message || 'Error al eliminar la publicación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la publicación');
        });
    }
}

// Modificación para la función guardarPublicacion() en script.js

// Función para guardar publicación (actualizada)
function guardarPublicacion(postId) {
    fetch('guardar_publicacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id_publicacion: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'saved') {
                // Mostrar mensaje de guardado exitoso
                const button = document.querySelector(`#post_${postId} .opciones-lista button:last-child`);
                if (button) {
                    button.textContent = 'Desguardar';
                }
                alert('Publicación guardada en tu colección');
            } else if (data.action === 'removed') {
                // Mostrar mensaje de eliminación exitosa
                const button = document.querySelector(`#post_${postId} .opciones-lista button:last-child`);
                if (button) {
                    button.textContent = 'Guardar';
                }
                alert('Publicación eliminada de tu colección');
            }
        } else {
            alert(data.message || 'Error al procesar la publicación');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la publicación');
    });
}

// Función para editar publicación - código existente...
function editarPublicacion(postId) {
    // Código existente...
}

// Función para mostrar/ocultar el formulario de evento
function toggleEventoForm() {
    const eventoForm = document.getElementById('evento-form');
    const tipoInput = document.getElementById('tipo-publicacion');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!eventoForm) {
        console.error('No se encontró el elemento evento-form');
        return;
    }
    
    if (eventoForm.style.display === 'none' || eventoForm.style.display === '') {
        eventoForm.style.display = 'block';
        tipoInput.value = 'evento';
        submitBtn.textContent = 'Publicar evento';
        submitBtn.classList.add('publicar-evento');
    } else {
        eventoForm.style.display = 'none';
        tipoInput.value = 'normal';
        submitBtn.textContent = 'Publicar';
        submitBtn.classList.remove('publicar-evento');
    }
}

// Inicialización al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializaciones para el formulario de evento
    const form = document.getElementById('publish-form');
    const tipoInput = document.getElementById('tipo-publicacion');
    const eventoBtn = document.getElementById('eventoBtn');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            // Si es un evento, validamos que tenga al menos título y fecha
            if (tipoInput && tipoInput.value === 'evento') {
                const titulo = document.getElementById('evento_titulo').value.trim();
                const fecha = document.getElementById('evento_fecha').value.trim();
                
                if (!titulo || !fecha) {
                    event.preventDefault();
                    
                    // Mostrar mensaje de error
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Para crear un evento, necesitas al menos indicar un título y una fecha.';
                    
                    // Eliminar mensaje anterior si existe
                    const oldError = document.querySelector('.error-message');
                    if (oldError) {
                        oldError.remove();
                    }
                    
                    // Insertar mensaje antes del botón de envío
                    const submitBtn = document.getElementById('submitBtn');
                    form.insertBefore(errorMsg, submitBtn);
                    
                    // Resaltar campos obligatorios
                    if (!titulo) {
                        document.getElementById('evento_titulo').classList.add('campo-requerido');
                    }
                    if (!fecha) {
                        document.getElementById('evento_fecha').classList.add('campo-requerido');
                    }
                    
                    // Scroll hacia el mensaje de error
                    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    return false;
                }
            }
        });
    }
    
    // Quitar la clase de error cuando se escribe en los campos
    document.querySelectorAll('.evento-input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('campo-requerido');
        });
    });
    
    // Manejar el clic en el botón de evento
    if (eventoBtn) {
        eventoBtn.addEventListener('click', toggleEventoForm);
    }
});