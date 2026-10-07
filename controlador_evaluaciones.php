<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Devuelve una respuesta JSON.
function responder(string $estado, string $mensaje = '', $datos = null, int $codigoHttp = 200): void
{
    http_response_code($codigoHttp);
    $respuesta = ['status' => $estado];
    if ($mensaje !== '') {
        $respuesta['message'] = $mensaje;
    }
    if ($datos !== null) {
        $respuesta['data'] = $datos;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}

// Comprueba la sesión y devuelve el identificador del usuario autenticado.
function usuarioAutenticado(): int
{
    if (!isset($_SESSION['idUsuario'])) {
        responder('error', 'Debe iniciar sesión para realizar esta acción.', null, 401);
        exit;
    }
    return (int) $_SESSION['idUsuario'];
}

$accion = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $nombreBase = getenv('DB_NAME') ?: 'tp2_programacion3';
    $usuarioBase = getenv('DB_USER') ?: 'root';
    $claveBase = getenv('DB_PASSWORD') ?: 'mia25';
    $pdo = new PDO(
        "mysql:host={$host};dbname={$nombreBase};charset=utf8mb4",
        $usuarioBase,
        $claveBase,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    switch ($accion) {
        case 'registro':
            $usuario = preg_replace('/[\s\p{Z}]+/u', '_', trim($_POST['usuario'] ?? ''));
            if ($usuario === null) {
                responder('error', 'El nombre de usuario contiene caracteres no válidos.', null, 422);
                break;
            }
            $password = $_POST['password'] ?? '';

            if (!preg_match('/^[\p{L}\p{N}_.-]{3,50}$/u', $usuario)) {
                responder('error', 'El usuario debe tener entre 3 y 50 caracteres (letras, números, punto, guion o guion bajo).', null, 422);
                break;
            }
            if (strlen($password) < 8) {
                responder('error', 'La contraseña debe tener al menos 8 caracteres.', null, 422);
                break;
            }

            $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
            $consulta->execute([$usuario]);
            if ($consulta->fetch()) {
                responder('error', 'El usuario ya existe.', null, 409);
                break;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $alta = $pdo->prepare('INSERT INTO usuarios (usuario, password) VALUES (?, ?)');
            $alta->execute([$usuario, $hash]);
            responder('success', 'Usuario registrado correctamente. Ya puede iniciar sesión.');
            break;

        case 'login':
            $usuario = preg_replace('/[\s\p{Z}]+/u', '_', trim($_POST['usuario'] ?? ''));
            if ($usuario === null) {
                responder('error', 'Usuario o contraseña incorrectos.', null, 401);
                break;
            }
            $password = $_POST['password'] ?? '';
            $consulta = $pdo->prepare('SELECT id, usuario, password FROM usuarios WHERE usuario = ?');
            $consulta->execute([$usuario]);
            $registro = $consulta->fetch();

            if (!$registro || !password_verify($password, $registro['password'])) {
                responder('error', 'Usuario o contraseña incorrectos.', null, 401);
                break;
            }

            session_regenerate_id(true);
            $_SESSION['idUsuario'] = (int) $registro['id'];
            $_SESSION['usuario'] = $registro['usuario'];
            responder('success', 'Sesión iniciada.', [
                'id' => (int) $registro['id'],
                'usuario' => $registro['usuario'],
            ]);
            break;

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            responder('success', 'Sesión cerrada.');
            break;

        case 'sesion':
            responder('success', '', [
                'autenticado' => isset($_SESSION['idUsuario']),
                'usuario' => $_SESSION['usuario'] ?? null,
            ]);
            break;

        case 'listar_examenes':
            $idUsuario = usuarioAutenticado();
            $consulta = $pdo->prepare('SELECT id, nombreExamen FROM examen WHERE idUsuario = ? ORDER BY nombreExamen');
            $consulta->execute([$idUsuario]);
            responder('success', '', $consulta->fetchAll());
            break;

        case 'guardar_examen':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_POST['idExamen'] ?? 0);
            $nombre = trim($_POST['nombreExamen'] ?? '');
            if ($nombre === '' || mb_strlen($nombre) > 100) {
                responder('error', 'El nombre del examen es obligatorio y no puede superar los 100 caracteres.', null, 422);
                break;
            }

            if ($idExamen > 0) {
                $actualizar = $pdo->prepare('UPDATE examen SET nombreExamen = ? WHERE id = ? AND idUsuario = ?');
                $actualizar->execute([$nombre, $idExamen, $idUsuario]);
                if ($actualizar->rowCount() === 0) {
                    $verificar = $pdo->prepare('SELECT id FROM examen WHERE id = ? AND idUsuario = ?');
                    $verificar->execute([$idExamen, $idUsuario]);
                    if (!$verificar->fetch()) {
                        responder('error', 'No se encontró el examen solicitado.', null, 404);
                        break;
                    }
                }
                responder('success', 'Examen actualizado.');
            } else {
                $alta = $pdo->prepare('INSERT INTO examen (idUsuario, nombreExamen) VALUES (?, ?)');
                $alta->execute([$idUsuario, $nombre]);
                responder('success', 'Examen creado.', ['id' => (int) $pdo->lastInsertId()]);
            }
            break;

        case 'eliminar_examen':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_POST['idExamen'] ?? 0);
            $baja = $pdo->prepare('DELETE FROM examen WHERE id = ? AND idUsuario = ?');
            $baja->execute([$idExamen, $idUsuario]);
            if ($baja->rowCount() === 0) {
                responder('error', 'No se encontró el examen solicitado.', null, 404);
                break;
            }
            responder('success', 'Examen eliminado junto con sus preguntas.');
            break;

        case 'listar_preguntas':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_GET['idExamen'] ?? 0);
            $consulta = $pdo->prepare(
                'SELECT p.id, p.textoPregunta
                 FROM preguntas p
                 INNER JOIN examen e ON e.id = p.idExamen
                 WHERE p.idExamen = ? AND e.idUsuario = ?
                 ORDER BY p.id'
            );
            $consulta->execute([$idExamen, $idUsuario]);
            responder('success', '', $consulta->fetchAll());
            break;

        case 'guardar_pregunta':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_POST['idExamen'] ?? 0);
            $idPregunta = (int) ($_POST['idPregunta'] ?? 0);
            $texto = trim($_POST['textoPregunta'] ?? '');

            $propietario = $pdo->prepare('SELECT id FROM examen WHERE id = ? AND idUsuario = ?');
            $propietario->execute([$idExamen, $idUsuario]);
            if (!$propietario->fetch()) {
                responder('error', 'No se encontró el examen solicitado.', null, 404);
                break;
            }
            if ($texto === '') {
                responder('error', 'El texto de la pregunta es obligatorio.', null, 422);
                break;
            }

            if ($idPregunta > 0) {
                $actualizar = $pdo->prepare(
                    'UPDATE preguntas SET textoPregunta = ? WHERE id = ? AND idExamen = ? AND idUsuario = ?'
                );
                $actualizar->execute([$texto, $idPregunta, $idExamen, $idUsuario]);
                if ($actualizar->rowCount() === 0) {
                    $verificar = $pdo->prepare(
                        'SELECT id FROM preguntas WHERE id = ? AND idExamen = ? AND idUsuario = ?'
                    );
                    $verificar->execute([$idPregunta, $idExamen, $idUsuario]);
                    if (!$verificar->fetch()) {
                        responder('error', 'No se encontró la pregunta solicitada.', null, 404);
                        break;
                    }
                }
                responder('success', 'Pregunta actualizada.');
            } else {
                $alta = $pdo->prepare(
                    'INSERT INTO preguntas (idExamen, idUsuario, textoPregunta) VALUES (?, ?, ?)'
                );
                $alta->execute([$idExamen, $idUsuario, $texto]);
                responder('success', 'Pregunta agregada.', ['id' => (int) $pdo->lastInsertId()]);
            }
            break;

        case 'eliminar_pregunta':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_POST['idExamen'] ?? 0);
            $idPregunta = (int) ($_POST['idPregunta'] ?? 0);
            $baja = $pdo->prepare(
                'DELETE p FROM preguntas p
                 INNER JOIN examen e ON e.id = p.idExamen
                 WHERE p.id = ? AND p.idExamen = ? AND e.idUsuario = ?'
            );
            $baja->execute([$idPregunta, $idExamen, $idUsuario]);
            if ($baja->rowCount() === 0) {
                responder('error', 'No se encontró la pregunta solicitada.', null, 404);
                break;
            }
            responder('success', 'Pregunta eliminada.');
            break;

        case 'sortear_preguntas':
            $idUsuario = usuarioAutenticado();
            $idExamen = (int) ($_GET['idExamen'] ?? 0);
            $cantidad = filter_var($_GET['cantidad'] ?? null, FILTER_VALIDATE_INT);
            if ($idExamen <= 0 || $cantidad === false || $cantidad === null || $cantidad <= 0) {
                responder('error', 'Seleccione un examen e indique una cantidad válida.', null, 422);
                break;
            }

            $propietario = $pdo->prepare('SELECT id FROM examen WHERE id = ? AND idUsuario = ?');
            $propietario->execute([$idExamen, $idUsuario]);
            if (!$propietario->fetch()) {
                responder('error', 'No se encontró el examen solicitado.', null, 404);
                break;
            }

            $total = $pdo->prepare('SELECT COUNT(*) FROM preguntas WHERE idExamen = ? AND idUsuario = ?');
            $total->execute([$idExamen, $idUsuario]);
            $disponibles = (int) $total->fetchColumn();
            if ($cantidad > $disponibles) {
                responder('error', "Solo hay {$disponibles} pregunta(s) disponible(s) para este examen.", null, 422);
                break;
            }

            $sorteo = $pdo->prepare(
                'SELECT id, textoPregunta FROM preguntas
                 WHERE idExamen = ? AND idUsuario = ?
                 ORDER BY RAND() LIMIT ?'
            );
            $sorteo->bindValue(1, $idExamen, PDO::PARAM_INT);
            $sorteo->bindValue(2, $idUsuario, PDO::PARAM_INT);
            $sorteo->bindValue(3, $cantidad, PDO::PARAM_INT);
            $sorteo->execute();
            responder('success', '', $sorteo->fetchAll());
            break;

        default:
            responder('error', 'Acción no válida.', null, 400);
    }
} catch (PDOException $e) {
    error_log('Error de base de datos en controlador_evaluaciones.php: ' . $e->getMessage());
    responder('error', 'No se pudo completar la operación por un problema de base de datos.', null, 500);
} catch (Throwable $e) {
    error_log('Error en controlador_evaluaciones.php: ' . $e->getMessage());
    responder('error', 'Ocurrió un error inesperado.', null, 500);
}
