// Espera a que cargue el documento y configura la interfaz y sus eventos.
$(function () {
    let examenes = [];
    let preguntas = [];
    let alertaTimer;

    // Muestra un mensaje temporal de éxito, error o advertencia en la página.
    function mostrarAlerta(mensaje, tipo) {
        clearTimeout(alertaTimer);
        $('#alertaMensaje')
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass(`alert-${tipo}`)
            .text(mensaje);
        alertaTimer = setTimeout(function () {
            $('#alertaMensaje').addClass('d-none');
        }, 6000);
    }

    // Envía una acción al controlador PHP y muestra los errores de la petición.
    function llamar(action, method, data) {
        const datos = typeof data === 'string'
            ? `${data}&action=${encodeURIComponent(action)}`
            : $.extend({ action: action }, data || {});
        return $.ajax({
            url: 'controlador_evaluaciones.php',
            type: method,
            data: datos,
            dataType: 'json'
        }).fail(function (xhr) {
            const mensaje = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'No se pudo conectar con el servidor. Intente nuevamente.';
            mostrarAlerta(mensaje, 'danger');
        });
    }

    // Normaliza el usuario para que los espacios se guarden como guiones bajos.
    function normalizarUsuario(valor) {
        return valor.trim().replace(/[\s\u00a0]+/g, '_');
    }

    // Vacía el formulario de examen y restaura el modo de creación.
    function limpiarFormularioExamen() {
        $('#formExamen')[0].reset();
        $('#idExamenEditar').val('');
        $('#btnGuardarExamen').text('Crear examen');
        $('#btnCancelarExamen').addClass('d-none');
    }

    // Limpia los campos de pregunta sin cambiar el examen seleccionado.
    function limpiarFormularioPregunta() {
        const idExamen = $('#examenPreguntas').val();
        $('#formPregunta')[0].reset();
        $('#examenPreguntas').val(idExamen);
        $('#idPreguntaEditar').val('');
        $('#btnGuardarPregunta').text('Agregar pregunta');
        $('#btnCancelarPregunta').addClass('d-none');
    }

    // Renderiza la lista de exámenes y sincroniza los selectores de la pantalla.
    function renderizarExamenes() {
        const examenSorteoActual = $('#selectExamen').val();
        const examenPreguntasActual = $('#examenPreguntas').val();
        const $lista = $('#listaExamenes').empty();
        const $selectPregunta = $('#examenPreguntas').empty();
        const $selectSorteo = $('#selectExamen').empty();

        if (examenes.length === 0) {
            $lista.append($('<li>').addClass('list-group-item text-muted').text('Todavía no hay exámenes.'));
            $selectPregunta.append($('<option>').val('').text('Primero cree un examen'));
            $selectSorteo.append($('<option>').val('').text('Primero cree un examen'));
            renderizarPreguntas([]);
            return;
        }

        examenes.forEach(function (examen) {
            const id = String(examen.id);
            const $fila = $('<li>').addClass('list-group-item d-flex justify-content-between align-items-center gap-2');
            $fila.append($('<span>').addClass('text-break').text(examen.nombreExamen));

            const $acciones = $('<div>').addClass('btn-group btn-group-sm flex-shrink-0');
            $acciones.append($('<button>').attr({ type: 'button', class: 'btn btn-outline-primary btn-editar-examen', 'data-id': id }).text('Editar'));
            $acciones.append($('<button>').attr({ type: 'button', class: 'btn btn-outline-danger btn-eliminar-examen', 'data-id': id }).text('Eliminar'));
            $fila.append($acciones);
            $lista.append($fila);

            $selectPregunta.append($('<option>').val(id).text(examen.nombreExamen));
            $selectSorteo.append($('<option>').val(id).text(examen.nombreExamen));
        });

        const idsDisponibles = examenes.map(function (examen) { return String(examen.id); });
        const idActivo = idsDisponibles.includes(examenSorteoActual)
            ? examenSorteoActual
            : (idsDisponibles.includes(examenPreguntasActual) ? examenPreguntasActual : idsDisponibles[0]);
        seleccionarExamen(idActivo);
    }

    // Mantiene seleccionados el mismo examen para preguntas y para sorteo.
    function seleccionarExamen(idExamen) {
        limpiarFormularioPregunta();
        $('#selectExamen').val(idExamen);
        $('#examenPreguntas').val(idExamen);
        cargarPreguntas(idExamen);
    }

    // Solicita al servidor los exámenes del usuario y actualiza la interfaz.
    function cargarExamenes() {
        return llamar('listar_examenes', 'GET').done(function (respuesta) {
            examenes = respuesta.data;
            renderizarExamenes();
        });
    }

    // Renderiza las preguntas disponibles y actualiza el límite del sorteo.
    function renderizarPreguntas(items) {
        preguntas = items;
        const $lista = $('#listaPreguntas').empty();
        if (items.length === 0) {
            $lista.append($('<li>').addClass('list-group-item text-muted').text(
                examenes.length ? 'Este examen todavía no tiene preguntas.' : 'Seleccione un examen.'
            ));
        } else {
            items.forEach(function (pregunta) {
                const $fila = $('<li>').addClass('list-group-item d-flex justify-content-between align-items-start gap-2');
                $fila.append($('<span>').addClass('text-break').text(pregunta.textoPregunta));
                const $acciones = $('<div>').addClass('btn-group btn-group-sm flex-shrink-0');
                $acciones.append($('<button>').attr({ type: 'button', class: 'btn btn-outline-primary btn-editar-pregunta', 'data-id': pregunta.id }).text('Editar'));
                $acciones.append($('<button>').attr({ type: 'button', class: 'btn btn-outline-danger btn-eliminar-pregunta', 'data-id': pregunta.id }).text('Eliminar'));
                $fila.append($acciones);
                $lista.append($fila);
            });
        }

        const cantidad = items.length;
        const $inputCantidad = $('#inputCantidad');
        $inputCantidad.prop('disabled', cantidad === 0);
        $('#btnSortear').prop('disabled', cantidad === 0);
        if (cantidad > 0) {
            $inputCantidad.attr('max', cantidad);
            $('#ayudaCantidad').text(
                `${cantidad} pregunta(s) disponible(s); elija una cantidad entre 1 y ${cantidad}.`
            );
            if (Number($inputCantidad.val()) > cantidad) {
                $inputCantidad.val(cantidad);
            }
        } else {
            $inputCantidad.removeAttr('max').val(1);
            $('#ayudaCantidad').text('Este examen no tiene preguntas. Agregue preguntas en Banco de preguntas para poder sortear.');
        }
    }

    // Solicita las preguntas de un examen y descarta respuestas ya obsoletas.
    function cargarPreguntas(idExamen) {
        if (!idExamen) {
            renderizarPreguntas([]);
            return;
        }
        preguntas = [];
        $('#inputCantidad, #btnSortear').prop('disabled', true);
        $('#ayudaCantidad').text('Cargando preguntas disponibles...');
        llamar('listar_preguntas', 'GET', { idExamen: idExamen }).done(function (respuesta) {
            if ($('#selectExamen').val() !== String(idExamen)) {
                return;
            }
            renderizarPreguntas(respuesta.data);
            $('#resultadoSorteo').empty().append(
                $('<li>').addClass('list-group-item text-muted').text('Seleccione la cantidad y presione Sortear.')
            );
        });
    }

    // Consulta el estado de autenticación y muestra el panel correspondiente.
    function cargarSesion() {
        llamar('sesion', 'GET').done(function (respuesta) {
            const autenticado = respuesta.data.autenticado;
            $('#panelAcceso').toggleClass('d-none', autenticado);
            $('#panelAplicacion').toggleClass('d-none', !autenticado);
            $('#usuarioInfo').toggleClass('d-none', !autenticado).toggleClass('d-flex', autenticado);
            $('#nombreUsuario').text(autenticado ? `Hola, ${respuesta.data.usuario}` : '');
            if (autenticado) {
                cargarExamenes();
            } else {
                examenes = [];
                preguntas = [];
            }
        });
    }

    $('#formRegistro').on('submit', function (evento) {
        evento.preventDefault();
        const usuario = normalizarUsuario($('#regUsuario').val());
        $('#regUsuario').val(usuario);
        llamar('registro', 'POST', $(this).serialize()).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            $('#formRegistro')[0].reset();
            $('#loginUsuario').val(usuario);
        });
    });

    $('#formLogin').on('submit', function (evento) {
        evento.preventDefault();
        $('#loginUsuario').val(normalizarUsuario($('#loginUsuario').val()));
        llamar('login', 'POST', $(this).serialize()).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            $('#formLogin')[0].reset();
            cargarSesion();
        });
    });

    $('#btnCerrarSesion').on('click', function () {
        llamar('logout', 'POST').done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            limpiarFormularioExamen();
            limpiarFormularioPregunta();
            $('#listaExamenes, #listaPreguntas, #resultadoSorteo').empty();
            cargarSesion();
        });
    });

    $('#formExamen').on('submit', function (evento) {
        evento.preventDefault();
        llamar('guardar_examen', 'POST', {
            idExamen: $('#idExamenEditar').val(),
            nombreExamen: $('#nombreExamen').val()
        }).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            limpiarFormularioExamen();
            cargarExamenes();
        });
    });

    $('#btnCancelarExamen').on('click', limpiarFormularioExamen);

    $('#listaExamenes').on('click', '.btn-editar-examen', function () {
        const examen = examenes.find(function (item) { return String(item.id) === String($(this).data('id')); }.bind(this));
        if (examen) {
            $('#idExamenEditar').val(examen.id);
            $('#nombreExamen').val(examen.nombreExamen).trigger('focus');
            $('#btnGuardarExamen').text('Guardar cambios');
            $('#btnCancelarExamen').removeClass('d-none');
        }
    });

    $('#listaExamenes').on('click', '.btn-eliminar-examen', function () {
        const idExamen = $(this).data('id');
        if (!window.confirm('¿Eliminar este examen y todas sus preguntas?')) {
            return;
        }
        llamar('eliminar_examen', 'POST', { idExamen: idExamen }).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            limpiarFormularioExamen();
            limpiarFormularioPregunta();
            cargarExamenes();
        });
    });

    $('#examenPreguntas').on('change', function () {
        seleccionarExamen($(this).val());
    });

    $('#selectExamen').on('change', function () {
        seleccionarExamen($(this).val());
    });

    $('#formPregunta').on('submit', function (evento) {
        evento.preventDefault();
        const idExamen = $('#examenPreguntas').val();
        llamar('guardar_pregunta', 'POST', {
            idExamen: idExamen,
            idPregunta: $('#idPreguntaEditar').val(),
            textoPregunta: $('#textoPregunta').val()
        }).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            limpiarFormularioPregunta();
            cargarPreguntas(idExamen);
        });
    });

    $('#btnCancelarPregunta').on('click', limpiarFormularioPregunta);

    $('#listaPreguntas').on('click', '.btn-editar-pregunta', function () {
        const idPregunta = $(this).data('id');
        const pregunta = preguntas.find(function (item) { return String(item.id) === String(idPregunta); });
        if (pregunta) {
            $('#idPreguntaEditar').val(pregunta.id);
            $('#textoPregunta').val(pregunta.textoPregunta).trigger('focus');
            $('#btnGuardarPregunta').text('Guardar cambios');
            $('#btnCancelarPregunta').removeClass('d-none');
        }
    });

    $('#listaPreguntas').on('click', '.btn-eliminar-pregunta', function () {
        const idPregunta = $(this).data('id');
        if (!window.confirm('¿Eliminar esta pregunta?')) {
            return;
        }
        llamar('eliminar_pregunta', 'POST', {
            idExamen: $('#examenPreguntas').val(),
            idPregunta: idPregunta
        }).done(function (respuesta) {
            mostrarAlerta(respuesta.message, 'success');
            cargarPreguntas($('#examenPreguntas').val());
        });
    });

    $('#formSorteo').on('submit', function (evento) {
        evento.preventDefault();
        const idExamen = $('#selectExamen').val();
        const cantidad = Number($('#inputCantidad').val());
        if (!idExamen || $('#examenPreguntas').val() !== idExamen || !Number.isInteger(cantidad) || cantidad < 1 || cantidad > preguntas.length) {
            mostrarAlerta(`Indique una cantidad entre 1 y ${preguntas.length} para el examen seleccionado.`, 'warning');
            return;
        }

        llamar('sortear_preguntas', 'GET', {
            idExamen: idExamen,
            cantidad: cantidad
        }).done(function (respuesta) {
            const $lista = $('#resultadoSorteo').empty();
            respuesta.data.forEach(function (pregunta) {
                $lista.append($('<li>').addClass('list-group-item').text(pregunta.textoPregunta));
            });
            if (respuesta.data.length === 0) {
                $lista.append($('<li>').addClass('list-group-item text-muted').text('No hay preguntas disponibles.'));
            }
        });
    });

    cargarSesion();
});
