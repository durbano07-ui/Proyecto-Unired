// Enviar mensaje con archivo
function enviarMensaje() {
    let mensaje = document.getElementById("mensaje").value;
    let archivo = document.getElementById("archivo").files[0]; // Obtener el archivo seleccionado

    if (!mensaje || !usuarioSeleccionado) {
        console.warn("Mensaje vacío o sin usuario seleccionado");
        return;
    }

    // Crear el FormData para enviar el mensaje y el archivo
    let formData = new FormData();
    formData.append('remitente', miID);
    formData.append('chat_id', usuarioSeleccionado);
    formData.append('mensaje', mensaje);
    if (archivo) {
        formData.append('archivo', archivo); // Agregar archivo al FormData
    }

    fetch("enviar_mensaje.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Mensaje enviado correctamente:", data);
        document.getElementById("mensaje").value = "";
        document.getElementById("archivo").value = ""; // Limpiar archivo
        cargarMensajes();
    })
    .catch(error => console.error("Error al enviar mensaje:", error));
}
function cargarMensajes() {
    if (!usuarioSeleccionado) return;

    fetch(`obtener_mensajes.php?remitente=${miID}&destinatario=${usuarioSeleccionado}`)
        .then(response => response.json())
        .then(mensajes => {
            let contenedorMensajes = document.getElementById("mensajes");
            contenedorMensajes.innerHTML = "";

            mensajes.forEach(m => {
                let mensajeContainer = document.createElement("div");
                mensajeContainer.className = m.id_remitente == miID ? "mensaje-propio-container" : "mensaje-ajeno-container";

                let mensajeDiv = document.createElement("div");
                mensajeDiv.textContent = m.contenido;
                mensajeDiv.className = m.id_remitente == miID ? "mensaje-propio" : "mensaje-ajeno";

                let fechaDiv = document.createElement("div");
                fechaDiv.textContent = m.fecha_envio;
                fechaDiv.className = "mensaje-fecha";

                mensajeContainer.appendChild(mensajeDiv);
                mensajeContainer.appendChild(fechaDiv);

                // Verificar si hay archivo adjunto
                if (m.archivo_url) {
                    let archivoLink = document.createElement("a");
                    archivoLink.href = m.archivo_url;
                    archivoLink.textContent = "Descargar archivo";
                    archivoLink.target = "_blank";
                    archivoLink.className = "mensaje-archivo";
                    mensajeContainer.appendChild(archivoLink);
                }

                contenedorMensajes.appendChild(mensajeContainer);
            });

            contenedorMensajes.scrollTop = contenedorMensajes.scrollHeight;
        })
        .catch(error => console.error("Error al cargar mensajes:", error));
}
document.getElementById("nuevo-chat-btn").addEventListener("click", function() {
    // Mostrar el contenedor con la lista de usuarios
    document.getElementById("usuarios-lista").style.display = "block";

    // Obtener la lista de usuarios desde el servidor (con AJAX)
    fetch("chat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "action=get_usuarios"
    })
    .then(response => response.json())
    .then(data => {
        let usuariosUl = document.getElementById("usuarios-ul");
        usuariosUl.innerHTML = ""; // Limpiar la lista actual

        // Mostrar los usuarios disponibles
        data.forEach(usuario => {
            let li = document.createElement("li");
            li.textContent = `${usuario.nombre} ${usuario.apellido}`;
            li.addEventListener("click", () => iniciarChat(usuario.id));
            usuariosUl.appendChild(li);
        });
    });
});

// Función para iniciar un chat
function iniciarChat(usuarioId) {
    // Aquí puedes redirigir a una página de chat específica o cargar un chat
    window.location.href = `chat.php?usuario_id=${usuarioId}`;
}