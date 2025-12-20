<?php
// ==========================================
// 1. BACKEND: PHP & SQL
// ==========================================
// Aquí ocurre la lógica del servidor y la conexión a datos.

$host = 'localhost';
$db   = 'siga_unalm_2025';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error Crítico de Conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGA 2025 - Monitor de Riesgo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        /* Indicadores de Prioridad (Semáforo) */
        .prioridad-ALTA {
            background-color: #fff0f0;
            border-left: 6px solid #dc3545;
        }

        .prioridad-MEDIA {
            background-color: #fff9db;
            border-left: 6px solid #ffc107;
        }

        .prioridad-BAJA {
            background-color: #f0fdf4;
            border-left: 6px solid #198754;
        }

        /* Caso Severo (Violencia/Acoso) */
        .caso-severo {
            background: linear-gradient(90deg, #2c0b0e 0%, #4a1318 100%) !important;
            color: white !important;
            border-left: 6px solid #000;
        }

        .caso-severo td {
            color: white !important;
            border-bottom: 1px solid #6c1e26;
        }

        .caso-severo .badge {
            background-color: #ff3333 !important;
            border: 1px solid white;
        }

        .caso-severo .btn {
            border-color: white;
            color: white;
        }

        .caso-severo .btn:hover {
            background-color: white;
            color: black;
        }

        /* Efectos Hover */
        tr {
            transition: all 0.2s;
        }

        tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
            position: relative;
        }
    </style>
</head>

<body>

    <div class="container mt-5 mb-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-primary"><i class="bi bi-bar-chart-fill"></i> SIGA UNALM</h2>
                <p class="text-muted mb-0">Sistema Integrado de Gestión y Alerta Académica</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-outline-dark me-2"><i class="bi bi-printer"></i> Imprimir</button>
                <button onclick="exportarExcel()" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Exportar</button>
            </div>
        </div>

        <div class="card mb-4 shadow-sm p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por Apellido, Código, Facultad o Riesgo..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="card shadow">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th>Código</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th class="text-center">Nota</th>
                            <th class="text-center">Asist.</th>
                            <th>Diagnóstico (SQL)</th>
                            <th class="text-end">Acciones (JS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ==========================================
                        // 4. LÓGICA SQL (CONSULTA RELACIONAL)
                        // ==========================================
                        $busqueda = $_GET['q'] ?? '';

                        $sql = "SELECT 
                                pa.codigo_unico, pa.nombre_completo, pa.facultad,
                                oa.nombre_asignatura,
                                pm.promedio_parcial, pm.porcentaje_asistencia, pm.id_matricula,
                                gi.categoria_riesgo, gi.nivel_prioridad, gi.responsable_registro
                            FROM PERFIL_ALUMNO pa
                            JOIN PERFORMANCE_MATRICULA pm ON pa.id_alumno = pm.id_alumno
                            JOIN OFERTA_ACADEMICA oa ON pm.id_oferta = oa.id_oferta
                            JOIN GESTION_INCIDENCIAS gi ON pm.id_matricula = gi.id_matricula ";

                        if ($busqueda) {
                            $sql .= "WHERE pa.nombre_completo LIKE :q OR pa.codigo_unico LIKE :q OR gi.categoria_riesgo LIKE :q ";
                        }

                        // Orden inteligente: Violencia primero, luego prioridad alta
                        $sql .= "ORDER BY 
                             CASE WHEN gi.categoria_riesgo LIKE '%VIOLENCIA%' THEN 0 ELSE 1 END,
                             FIELD(gi.nivel_prioridad, 'ALTA', 'MEDIA', 'BAJA'), 
                             pa.nombre_completo LIMIT 50";

                        $stmt = $pdo->prepare($sql);
                        if ($busqueda) $stmt->bindValue(':q', "%$busqueda%");
                        $stmt->execute();

                        // BUCLE PHP PARA GENERAR HTML
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            // Lógica PHP para clases CSS
                            $clase_css = "prioridad-" . $row['nivel_prioridad'];

                            if (strpos($row['categoria_riesgo'], 'VIOLENCIA') !== false || strpos($row['categoria_riesgo'], 'ACOSO') !== false) {
                                $clase_css = "caso-severo";
                            }

                            $badge_color = match ($row['nivel_prioridad']) {
                                'ALTA' => 'bg-danger',
                                'MEDIA' => 'bg-warning text-dark',
                                'BAJA' => 'bg-success',
                            };

                            // Renderizado de Fila
                            echo "<tr class='$clase_css'>
                                <td><span class='fw-bold font-monospace'>{$row['codigo_unico']}</span></td>
                                <td>
                                    {$row['nombre_completo']}
                                    <br><small class='opacity-75'>{$row['facultad']}</small>
                                </td>
                                <td><small>{$row['nombre_asignatura']}</small></td>
                                <td class='fw-bold text-center h5 mb-0'>{$row['promedio_parcial']}</td>
                                <td class='text-center'>{$row['porcentaje_asistencia']}%</td>
                                <td>
                                    <span class='badge $badge_color mb-1'>{$row['categoria_riesgo']}</span>
                                    <br><small class='fst-italic opacity-75'>By: {$row['responsable_registro']}</small>
                                </td>
                                <td class='text-end'>
                                    <button onclick=\"verDetalle('{$row['nombre_completo']}', '{$row['categoria_riesgo']}', '{$row['id_matricula']}')\" class='btn btn-sm btn-light border shadow-sm'>
                                        <i class='bi bi-eye'></i> Ver
                                    </button>
                                </td>
                              </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if ($stmt->rowCount() == 0): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-inbox fs-1"></i>
                    <p>No se encontraron alumnos con ese criterio.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Función 1: Alerta Bonita con SweetAlert2
        function verDetalle(nombre, riesgo, id) {
            Swal.fire({
                title: 'Expediente: ' + id,
                html: `<strong>Alumno:</strong> ${nombre}<br>
                   <strong>Diagnóstico:</strong> ${riesgo}<br><br>
                   ¿Desea ir a la gestión completa del caso?`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, ir al expediente',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirección con JS
                    window.location.href = `reportar.php?id=${id}`;
                }
            });
        }

        // Función 2: Simulación de Exportación
        function exportarExcel() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'success',
                title: 'Generando archivo Excel...'
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>