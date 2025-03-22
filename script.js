function editarPublicacion(postId) {
    console.log("Editando publicación: " + postId); // Añadir logs para depuración
    
    // Obtener el contenido actual de la publicación
    const postContent = document.querySelector(`#post_${postId} .post-content`);
    const contenidoTexto = document.querySelector(`#post_${postId} .post-content p`).textContent.trim();
    
    // Guardar el contenido original
    const contenidoOriginal = postContent.innerHTML;
    
    // Crear y mostrar el formulario de edición
    const editarHTML = `
        <div class="editar-form">
            <textarea id="editar-input-${postId}" class="editar-input">${contenidoTexto}</textarea>
            <div class="editar-buttons">
                <button onclick="guardarEdicion(${postId}, '${encodeURIComponent(contenidoOriginal)}')">Guardar</button>
                <button onclick="cancelarEdicion(${postId}, '${encodeURIComponent(contenidoOriginal)}')">Cancelar</button>
            </div>
            <p class="tiempo-info">Solo puedes editar publicaciones dentro de las primeras 2 horas.</p>
        </div>
    `;
    
    // Reemplazar el contenido con el formulario de edición
    postContent.innerHTML = editarHTML;
}

function guardarEdicion(postId, contenidoOriginalEncoded) {
    const contenidoOriginal = decodeURIComponent(contenidoOriginalEncoded);
    const contenido = document.getElementById(`editar-input-${postId}`).value.trim();
    
    if (contenido === '') {
        alert('El contenido no puede estar vacío');
        return;
    }
    
    fetch('editar_publicacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id_publicacion: postId,
            contenido: contenido
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar la página para reflejar los cambios
            window.location.reload();
        } else {
            alert(data.message);
            // Restaurar el contenido original en caso de error
            const postContent = document.querySelector(`#post_${postId} .post-content`);
            postContent.innerHTML = contenidoOriginal;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al actualizar la publicación');
        // Restaurar el contenido original en caso de error
        const postContent = document.querySelector(`#post_${postId} .post-content`);
        postContent.innerHTML = contenidoOriginal;
    });
}

function cancelarEdicion(postId, contenidoOriginalEncoded) {
    const contenidoOriginal = decodeURIComponent(contenidoOriginalEncoded);
    const postContent = document.querySelector(`#post_${postId} .post-content`);
    postContent.innerHTML = contenidoOriginal;
}
