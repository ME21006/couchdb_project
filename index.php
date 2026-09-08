<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/CouchDBConfig.php';
require_once __DIR__ . '/src/CouchDBDocumentRepository.php';
require_once __DIR__ . '/src/CouchDBBackup.php';

$message = '';
$messageType = '';
$result = null;

try {
    $connection = CouchDBConfig::createConnection();
    $repository = new CouchDBDocumentRepository($connection);
} catch (Throwable $e) {
    $connection = null;
    $repository = null;
    $message = 'No se pudo establecer la conexión: ' . $e->getMessage();
    $messageType = 'error';
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $connection !== null) {
    try {
        switch ($action) {
            case 'test_connection':
                $result = $connection->request('GET', '');
                $message = 'Conexión realizada correctamente con CouchDB.';
                $messageType = 'success';
                break;

            case 'create':
                $id = trim($_POST['id'] ?? '');
                $json = trim($_POST['json'] ?? '');
                if ($json === '') throw new RuntimeException('Debe ingresar los datos del documento.');
                $data = json_decode($json, true);
                if (!is_array($data)) throw new RuntimeException('El contenido ingresado no es un JSON válido.');
                $result = $repository->create($data, $id !== '' ? $id : null);
                $message = 'Documento creado correctamente.';
                $messageType = 'success';
                break;

            case 'find':
                $id = trim($_POST['id'] ?? '');
                if ($id === '') throw new RuntimeException('Debe ingresar el ID del documento.');
                $result = $repository->find($id);
                if (isset($result['error'])) {
                    throw new RuntimeException('No se encontró el documento: ' . ($result['reason'] ?? $result['error']));
                }
                $message = 'Documento encontrado correctamente.';
                $messageType = 'success';
                break;

            case 'update':
                $id = trim($_POST['id'] ?? '');
                $json = trim($_POST['json'] ?? '');
                if ($id === '') throw new RuntimeException('Debe ingresar el ID del documento.');
                if ($json === '') throw new RuntimeException('Debe ingresar los datos a actualizar.');
                $data = json_decode($json, true);
                if (!is_array($data)) throw new RuntimeException('El contenido ingresado no es un JSON válido.');
                $result = $repository->update($id, $data);
                $message = 'Documento actualizado correctamente.';
                $messageType = 'success';
                break;

            case 'delete':
                $id = trim($_POST['id'] ?? '');
                $rev = trim($_POST['rev'] ?? '');
                if ($id === '') throw new RuntimeException('Debe ingresar el ID del documento.');
                
                // Si no se proporcionó el _rev, intentamos buscar el documento para obtener su última revisión
                if ($rev === '') {
                    $doc = $repository->find($id);
                    if (isset($doc['error'])) {
                        throw new RuntimeException('No se encontró el documento para eliminar.');
                    }
                    $rev = $doc['_rev'] ?? '';
                }

                if ($rev === '') throw new RuntimeException('No se pudo obtener la revisión (_rev) del documento.');

                // Llamada al método delete del repositorio (o ejecútalo según el método definido en tu interfaz)
                if (method_exists($repository, 'delete')) {
                    $result = $repository->delete($id, $rev);
                } else {
                    // Fallback directo a la conexión CouchDB si el repositorio usa la llamada REST estándar
                    $result = $connection->request('DELETE', '/' . urlencode($id) . '?rev=' . urlencode($rev));
                }

                $message = 'Documento eliminado correctamente.';
                $messageType = 'success';
                break;

            case 'backup':
                $backupService = new CouchDBBackup($connection);
                $result = $backupService->exportDatabase();
                $message = 'Respaldo generado correctamente.';
                $messageType = 'success';
                break;
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

$resultJson = $result !== null
    ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión CouchDB</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f2f4f7;color:#222}header{background:#263238;color:white;padding:25px;text-align:center}header h1{margin:0 0 8px}header p{margin:0;color:#d7dee2}.container{width:95%;max-width:1200px;margin:30px auto}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px}.card{background:white;border-radius:10px;padding:22px;box-shadow:0 3px 12px rgba(0,0,0,.08)}.card h2{margin-top:0;color:#263238;font-size:20px}.card p{color:#666}label{display:block;margin-top:12px;margin-bottom:6px;font-weight:bold}input,textarea{width:100%;padding:11px;border:1px solid #ccc;border-radius:6px;font-family:Consolas,monospace;font-size:14px}textarea{min-height:130px;resize:vertical}button{margin-top:15px;padding:11px 18px;border:0;border-radius:6px;cursor:pointer;font-weight:bold;background:#37474f;color:white}button:hover{opacity:.88}.message{margin-bottom:20px;padding:15px;border-radius:7px;font-weight:bold}.success{background:#e8f5e9;border:1px solid #81c784;color:#2e7d32}.error{background:#ffebee;border:1px solid #e57373;color:#c62828}.result{margin-top:0}pre{background:#1e1e1e;color:#eee;padding:18px;border-radius:7px;overflow-x:auto;white-space:pre-wrap;word-wrap:break-word}.full{grid-column:1/-1}.connection-status{text-align:center}.backup-button{background:#546e7a}.delete-button{background:#c62828}@media(max-width:800px){.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style>
</head>
<body>
<header><h1>Sistema de Gestión CouchDB</h1><p>Interfaz gráfica del proyecto PHP</p></header>
<div class="container">
<?php if ($message !== ''): ?><div class="message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div class="grid">
<div class="card connection-status"><h2>Conexión</h2><p>Comprueba que PHP pueda comunicarse con CouchDB.</p><form method="POST"><input type="hidden" name="action" value="test_connection"><button type="submit">Probar conexión</button></form></div>
<div class="card connection-status"><h2>Respaldo</h2><p>Obtiene los documentos y genera un archivo JSON en <b>backups</b>.</p><form method="POST"><input type="hidden" name="action" value="backup"><button type="submit" class="backup-button">Generar respaldo</button></form></div>
<div class="card"><h2>Crear documento</h2><p>Indique un ID o déjelo vacío para que CouchDB genere uno.</p><form method="POST"><input type="hidden" name="action" value="create"><label>ID del documento (opcional)</label><input type="text" name="id" placeholder="estudiante001"><label>Documento JSON</label><textarea name="json" placeholder='{
    "tipo": "estudiante",
    "nombre": "Juan",
    "edad": 20
}'></textarea><button type="submit">Crear documento</button></form></div>
<div class="card"><h2>Buscar documento</h2><p>Consulta un documento utilizando su ID.</p><form method="POST"><input type="hidden" name="action" value="find"><label>ID del documento</label><input type="text" name="id" placeholder="estudiante001" required><button type="submit">Buscar documento</button></form></div>
<div class="card"><h2>Eliminar documento</h2><p>Elimina un documento indicando su ID (el <b>_rev</b> es opcional si se busca automáticamente).</p><form method="POST"><input type="hidden" name="action" value="delete"><label>ID del documento</label><input type="text" name="id" placeholder="estudiante001" required><button type="submit" class="delete-button">Eliminar documento</button></form></div>
<div class="card full"><h2>Actualizar documento</h2><p>Primero obtiene el documento y su <b>_rev</b>, después realiza la actualización.</p><form method="POST"><input type="hidden" name="action" value="update"><label>ID del documento</label><input type="text" name="id" placeholder="estudiante001" required><label>Datos a actualizar</label><textarea name="json" placeholder='{
    "nombre": "Juan Pérez",
    "edad": 21
}' required></textarea><button type="submit">Actualizar documento</button></form></div>
<?php if ($resultJson !== ''): ?><div class="card full result"><h2>Resultado de la operación</h2><pre><?= htmlspecialchars($resultJson) ?></pre></div><?php endif; ?>
</div></div>
</body>
</html>