<?php
require_once __DIR__ . '/BD.php';

class Horario
{
    /* Convierte un arreglo de días ['1','2','3'] a un texto "Lunes–Miércoles, Viernes".
    /* nombres de días completos y variables descriptivas */
    public static function diasATexto(array $dias): string {
        $mapDias = [
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miércoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sábado',
            '7' => 'Domingo'
        ];
        $dias = array_map('intval', $dias);
        sort($dias);
        // Compactar intervalos consecutivos (1..7)
        $rangos = [];
        $inicioRango = null;
        $diaPrevio = null;
        foreach ($dias as $dia) {
            if ($inicioRango === null) {
                $inicioRango = $diaPrevio = $dia;
                continue;
            }
            if ($dia === $diaPrevio + 1) {
                $diaPrevio = $dia; // El día es consecutivo, extendemos el rango
                continue;
            }
            // El día no es consecutivo, cerramos el rango anterior
            $rangos[] = [$inicioRango, $diaPrevio];
            // Empezamos un nuevo rango
            $inicioRango = $diaPrevio = $dia;
        }
        // Añadir el último rango que estaba en proceso
        if ($inicioRango !== null) {
            $rangos[] = [$inicioRango, $diaPrevio];
        }
        // Renderizar los segmentos de texto
        $segmentos = [];
        foreach ($rangos as [$inicio, $fin]) {
            if ($inicio === $fin) {
                // Caso 1: Día individual (ej. "Viernes")
                $segmentos[] = $mapDias[(string)$inicio];
            } else {
                // Caso 2: Rango de días (ej. "Lunes – Miércoles")
                $segmentos[] = $mapDias[(string)$inicio] . ' – ' . $mapDias[(string)$fin];
            }
        }
        return implode(', ', $segmentos);
    }

    /** Lista cruda (por día) con JOIN propietario */
    private function listarCrudo(string $q=''): array {
        $pdo = BD::pdo();
        $sql = "SELECT h.idHorario, h.propietarioId, h.dia_semana, h.horaEntrada, h.horaSalida, p.nombre 
                AS propNombre, p.email AS propEmail FROM horario h 
                JOIN propietario p ON p.idPropietario = h.propietarioId";
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE p.nombre LIKE :q OR p.email LIKE :q2";
            $params[':q']  = '%'.$q.'%';
            $params[':q2'] = '%'.$q.'%';
        }
        $sql .= " ORDER BY p.nombre ASC, h.horaEntrada ASC, h.dia_semana ASC";
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAgrupado(string $q=''): array {
        $rows = $this->listarCrudo($q);
        $grp = [];
        foreach ($rows as $r) {
            $k = $r['propietarioId'].'|'.$r['horaEntrada'].'|'.$r['horaSalida'];
            if (!isset($grp[$k])) {
                $grp[$k] = [
                    'idRepresentante' => (int)$r['idHorario'],
                    'propietarioId'   => (int)$r['propietarioId'],
                    'propNombre'      => $r['propNombre'],
                    'propEmail'       => $r['propEmail'],
                    'horaEntrada'     => $r['horaEntrada'],
                    'horaSalida'      => $r['horaSalida'],
                    'dias'            => []
                ];
            }
            $grp[$k]['dias'][] = (string)$r['dia_semana'];
        }
        // Normalizar orden de días
        foreach ($grp as &$g) { sort($g['dias']); }
        return array_values($grp);
    }

    public function crearMultiple(int $propietarioId, array $dias, string $he, string $hs): bool {
        $pdo = BD::pdo();
        $pdo->beginTransaction();
        try {
            if (!empty($dias)) {
                $in = implode(',', array_fill(0, count($dias), '?'));
                $del = $pdo->prepare("DELETE FROM horario WHERE propietarioId=? AND horaEntrada=? AND horaSalida=? AND dia_semana IN ($in)");
                $args = array_merge([$propietarioId, $he, $hs], array_map('intval',$dias));
                $del->execute($args);
            }
            // Inserta cada día
            $ins = $pdo->prepare("INSERT INTO horario (propietarioId, dia_semana, horaEntrada, horaSalida) VALUES (:pid, :d, :he, :hs)");
            foreach ($dias as $d) {
                $ins->execute([':pid'=>$propietarioId, ':d'=>(int)$d, ':he'=>$he, ':hs'=>$hs]);
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function actualizarBloque(int $propietarioId, array $dias, string $he, string $hs, string $old_he, string $old_hs): bool {
        $pdo = BD::pdo();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM horario WHERE propietarioId=:pid AND horaEntrada=:ohe AND horaSalida=:ohs");
            $del->execute([':pid'=>$propietarioId, ':ohe'=>$old_he, ':ohs'=>$old_hs]);
            // Inserta el nuevo bloque
            $ins = $pdo->prepare("INSERT INTO horario (propietarioId, dia_semana, horaEntrada, horaSalida) VALUES (:pid, :d, :he, :hs)");
            foreach ($dias as $d) {
                $ins->execute([':pid'=>$propietarioId, ':d'=>(int)$d, ':he'=>$he, ':hs'=>$hs]);
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /** Elimina un bloque completo (por propietario+ventana) */
    public function eliminarBloque(int $propietarioId, string $he, string $hs): bool {
        $pdo = BD::pdo();
        $st = $pdo->prepare("DELETE FROM horario WHERE propietarioId=:pid AND horaEntrada=:he AND horaSalida=:hs");
        return $st->execute([':pid'=>$propietarioId, ':he'=>$he, ':hs'=>$hs]);
    }

    /** Trae un horario (representante) para conocer propietario */
    public function obtenerPorId(int $id): ?array {
        $pdo = BD::pdo();
        $st = $pdo->prepare("SELECT idHorario, propietarioId, dia_semana, horaEntrada, horaSalida FROM horario WHERE idHorario=:id");
        $st->execute([':id'=>$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Para llenar el select de empleados */
    public function propietariosTodos(string $q=''): array {
        $pdo = BD::pdo();
        $sql = "SELECT idPropietario, nombre, email FROM propietario";
        $params = [];
        if ($q!=='') { $sql.=" WHERE nombre LIKE :q OR email LIKE :q2"; $params=[':q'=>'%'.$q.'%',':q2'=>'%'.$q.'%']; }
        $sql .= " ORDER BY nombre ASC";
        $st = $pdo->prepare($sql); $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}