<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Model/BD.php';
require_once __DIR__ . '/AppControlador.php';

class Dashboard extends AppControlador
{
    public function datos(): void {
        $accionActual = $_GET['accion'] ?? '';
        if ($accionActual !== 'auth.form') {
            $this->asegurarSesion();
        }
        // Permite admin, editor y consultor
        $rol = $_SESSION['usuario']['tipoUsuario'] ?? 'consultor';
        if (!in_array($rol, ['admin', 'editor', 'consultor'], true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Permiso denegado']);
            return;
        }
        try {
            $pdo = BD::pdo();
            $pdo->exec("SET NAMES utf8mb4");
            // === Totales base (comparten Admin/Editor) ===
            $total_contactos = (int)$pdo->query("SELECT COUNT(*) FROM propietario")->fetchColumn();
            $usuarios        = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
            $extensiones     = (int)$pdo->query("SELECT COUNT(*) FROM extension")->fetchColumn();
            // Distribución por áreas
            $sqlDeptos = "
                SELECT a.nombre AS name, COUNT(p.idPropietario) AS value
                FROM area a
                LEFT JOIN puesto pu     ON pu.areaId = a.idArea
                LEFT JOIN propietario p ON p.puestoId = pu.idPuesto
                GROUP BY a.idArea, a.nombre
                ORDER BY value DESC, a.nombre ASC
            ";
            $departamentos = $pdo->query($sqlDeptos)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $departamentos = array_map(fn($r) => ['name' => (string)$r['name'], 'value' => (int)$r['value']], $departamentos);
            // Actividad reciente (bitácora)
            $stmt = $pdo->prepare("
                SELECT descripcion AS text, creada_at
                FROM bitacorasistema
                ORDER BY creada_at DESC
                LIMIT 50
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $activity = array_map(function ($r) {
                $tsMs = strtotime($r['creada_at'] ?? 'now') * 1000;
                return ['text' => ($r['text'] ?? 'Actividad'), 'ts' => $tsMs];
            }, $rows);
            // Si más adelante agregas “solicitudes de cambio”, arma aquí tu arreglo:
            $solicitudes = []; // por ahora vacío
            $payload = [
                // Bloque “admin”
                'summary' => [
                    'total_contactos' => $total_contactos,
                    'usuarios'        => $usuarios,
                    'extensiones'     => $extensiones,
                    'departamentos'   => $departamentos,
                ],
                // Bloque “editor”: mapea a tus KPIs (ahorita reciclamos totales)
                'editorSummary' => [
                    'publicados'  => $usuarios,          // ejemplo: reasigna a lo que convenga
                    'revision'    => $total_contactos,   // idem
                    'borradores'  => $extensiones,       // idem
                    'programados' => count($departamentos),
                    'estados'     => array_map(fn($d, $i) => [
                        'name'  => $d['name'],
                        'value' => $d['value'],
                        // paleta simple
                        'color' => ['#6b8af7', '#c084fc', '#fbbf24', '#34d399', '#f87171', '#60a5fa', '#fb7185', '#22d3ee'][$i % 8]
                    ], $departamentos, array_keys($departamentos))
                ],
                'activity'   => $activity,
                'solicitudes' => $solicitudes,
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            error_log("[Dashboard] " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Error al obtener datos']);
        }
    }
}