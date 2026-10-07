<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de evaluaciones - Programación III</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container d-flex justify-content-between">
        <a class="navbar-brand" href="#">Gestor de evaluaciones</a>
        <div id="usuarioInfo" class="d-none align-items-center gap-3 text-white">
            <span id="nombreUsuario"></span>
            <button id="btnCerrarSesion" class="btn btn-outline-light btn-sm" type="button">Cerrar sesión</button>
        </div>
    </div>
</nav>

<main class="container pb-5">
    <div id="alertaMensaje" class="alert d-none" role="alert" aria-live="polite"></div>

    <section id="panelAcceso" class="row justify-content-center g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white"><h1 class="h5 mb-0">Crear una cuenta</h1></div>
                <div class="card-body">
                    <form id="formRegistro">
                        <div class="mb-3">
                            <label for="regUsuario" class="form-label">Usuario</label>
                            <input id="regUsuario" name="usuario" class="form-control" minlength="3" maxlength="50" required autocomplete="username">
                            <div class="form-text">De 3 a 50 caracteres. Si escribe espacios, se convertirán en guiones bajos.</div>
                        </div>
                        <div class="mb-3">
                            <label for="regPassword" class="form-label">Contraseña</label>
                            <input id="regPassword" name="password" type="password" class="form-control" minlength="8" required autocomplete="new-password">
                        </div>
                        <button class="btn btn-success w-100" type="submit">Registrarme</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h2 class="h5 mb-0">Iniciar sesión</h2></div>
                <div class="card-body">
                    <form id="formLogin">
                        <div class="mb-3">
                            <label for="loginUsuario" class="form-label">Usuario</label>
                            <input id="loginUsuario" name="usuario" class="form-control" required autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Contraseña</label>
                            <input id="loginPassword" name="password" type="password" class="form-control" required autocomplete="current-password">
                        </div>
                        <button class="btn btn-dark w-100" type="submit">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section id="panelAplicacion" class="d-none">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h2 class="h5 mb-0">Mis exámenes</h2></div>
                    <div class="card-body">
                        <form id="formExamen" class="mb-3">
                            <input id="idExamenEditar" type="hidden">
                            <label for="nombreExamen" class="form-label">Nombre del examen</label>
                            <div class="input-group">
                                <input id="nombreExamen" class="form-control" maxlength="100" required placeholder="Ej.: Programación III">
                                <button id="btnGuardarExamen" class="btn btn-primary" type="submit">Crear examen</button>
                            </div>
                            <button id="btnCancelarExamen" class="btn btn-link btn-sm d-none px-0" type="button">Cancelar edición</button>
                        </form>
                        <ul id="listaExamenes" class="list-group" aria-live="polite"></ul>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header"><h2 class="h5 mb-0">Banco de preguntas</h2></div>
                    <div class="card-body">
                        <form id="formPregunta">
                            <input id="idPreguntaEditar" type="hidden">
                            <div class="mb-3">
                                <label for="examenPreguntas" class="form-label">Examen</label>
                                <select id="examenPreguntas" class="form-select" required>
                                    <option value="">Primero cree un examen</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="textoPregunta" class="form-label">Pregunta</label>
                                <textarea id="textoPregunta" class="form-control" rows="3" required placeholder="Escriba la pregunta"></textarea>
                            </div>
                            <button id="btnGuardarPregunta" class="btn btn-primary" type="submit">Agregar pregunta</button>
                            <button id="btnCancelarPregunta" class="btn btn-link d-none" type="button">Cancelar edición</button>
                        </form>
                        <hr>
                        <h3 class="h6">Preguntas del examen</h3>
                        <ul id="listaPreguntas" class="list-group">
                            <li class="list-group-item text-muted">Seleccione un examen.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white"><h2 class="h5 mb-0">Sorteo de preguntas</h2></div>
                    <div class="card-body">
                        <form id="formSorteo" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="selectExamen" class="form-label">Examen</label>
                                <select id="selectExamen" class="form-select" required>
                                    <option value="">Primero cree un examen</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="inputCantidad" class="form-label">Cantidad</label>
                                <input id="inputCantidad" type="number" class="form-control" value="1" min="1" required>
                                <div id="ayudaCantidad" class="form-text" aria-live="polite"></div>
                            </div>
                            <div class="col-md-3">
                                <button id="btnSortear" class="btn btn-warning w-100" type="submit">Sortear</button>
                            </div>
                        </form>
                        <hr>
                        <h3 class="h6">Preguntas seleccionadas</h3>
                        <ol id="resultadoSorteo" class="list-group list-group-numbered">
                            <li class="list-group-item text-muted">Seleccione un examen y la cantidad de preguntas.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="gestor_sorteo.js?v=20261003-9"></script>
</body>
</html>
