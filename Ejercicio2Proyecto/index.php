<?php
// Definición de la clase abstracta y la clase EstadisticaBasica
abstract class Estadistica {
    abstract public function calcularMedia(array $datos): float;
    abstract public function calcularMediana(array $datos): float;
    abstract public function calcularModa(array $datos): float;
}

class EstadisticaBasica extends Estadistica {
    public function calcularMedia(array $datos): float {
        $this->validarDatos($datos);
        return array_sum($datos) / count($datos);
    }

    public function calcularMediana(array $datos): float {
        $this->validarDatos($datos);
        sort($datos);
        $mid = (int)(count($datos) / 2);
        return (count($datos) % 2 === 0)
            ? ($datos[$mid - 1] + $datos[$mid]) / 2
            : $datos[$mid];
    }

    public function calcularModa(array $datos): float {
        $this->validarDatos($datos);
        $frecuencias = array_count_values(array_map('strval', $datos));
        arsort($frecuencias);

        if (empty($frecuencias)) {
            throw new InvalidArgumentException("No se pudo calcular la moda para un conjunto vacío o sin valores únicos.");
        }

        // Encontrar la frecuencia máxima
        $maxFreq = 0;
        foreach ($frecuencias as $freq) {
            if ($freq > $maxFreq) {
                $maxFreq = $freq;
            }
        }

        // Recolectar todas las modas (valores con la frecuencia máxima)
        $modes = [];
        foreach ($frecuencias as $value => $freq) {
            if ($freq === $maxFreq) {
                $modes[] = (float)$value;
            }
        }

        // Si hay múltiples modas, devuelve la primera encontrada.
        // Si necesitas todas las modas, este método debería devolver un array.
        return $modes[0];
    }

    public function generalInforme(array $conjuntos): array {
        $resultado = [];
        foreach ($conjuntos as $nombre => $datos) {
            try {
                $resultado[$nombre] = [
                    'media' => round($this->calcularMedia($datos), 2),
                    'mediana' => round($this->calcularMediana($datos), 2),
                    'moda' => round($this->calcularModa($datos), 2)
                ];
            } catch (InvalidArgumentException $e) {
                // Captura errores de validación para conjuntos individuales
                $resultado[$nombre] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        return $resultado;
    }

    private function validarDatos(array $datos): void {
        if (count($datos) === 0) {
            throw new InvalidArgumentException("Conjunto de datos vacío");
        }
        foreach ($datos as $valor) {
            if (!is_numeric($valor)) {
                throw new InvalidArgumentException("Valor no numérico encontrado: '{$valor}'");
            }
        }
    }
}

// Inicialización de variables para la vista
$conjuntos = [];
$informe = [];
$error = '';
$conjuntosInput = []; // Para preservar los datos en el formulario después de un error

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conjuntosInput = $_POST['conjuntos'] ?? [];

        // Validar que hay al menos un conjunto
        if (empty($conjuntosInput)) {
            throw new InvalidArgumentException("Debe ingresar al menos un conjunto de datos.");
        }

        $nombresConjuntos = []; // Para detectar nombres duplicados

        foreach ($conjuntosInput as $index => $c) {
            $nombre = trim($c['nombre'] ?? '');
            $valoresStr = trim($c['valores'] ?? '');

            // Validar nombre
            if (empty($nombre)) {
                throw new InvalidArgumentException("El nombre del conjunto #" . ($index + 1) . " no puede estar vacío.");
            }

            // Verificar duplicados de nombre
            if (isset($nombresConjuntos[$nombre])) {
                throw new InvalidArgumentException("El nombre del conjunto '{$nombre}' está duplicado.");
            }
            $nombresConjuntos[$nombre] = true;

            // Validar valores
            if (empty($valoresStr)) {
                throw new InvalidArgumentException("Los valores del conjunto '{$nombre}' no pueden estar vacíos.");
            }

            // Convertir a array numérico
            // Primero, reemplazar espacios por comas para que explode funcione correctamente
            $valoresStrCleaned = preg_replace('/\s+/', ',', $valoresStr);
            // Luego, limpiar comas múltiples y espacios/comas al inicio/final
            $valoresStrCleaned = preg_replace('/,\s*,/', ',', $valoresStrCleaned); // Reemplazar ", ," por ","
            $valoresStrCleaned = trim($valoresStrCleaned, ' ,'); // Eliminar comas y espacios al inicio/final
            $valoresStrCleaned = preg_replace('/\s*,\s*/', ',', $valoresStrCleaned); // Normalizar espacios alrededor de comas (redundante después del primer paso, pero seguro)

            $valoresArray = explode(',', $valoresStrCleaned);
            $valoresArray = array_map('trim', $valoresArray);
            $valoresArray = array_filter($valoresArray, 'strlen'); // Eliminar elementos vacíos

            $numerosValidos = [];
            foreach ($valoresArray as $valor) {
                if (!is_numeric($valor)) {
                    throw new InvalidArgumentException("El valor '{$valor}' en el conjunto '{$nombre}' no es numérico.");
                }
                $numerosValidos[] = (float)$valor;
            }

            $conjuntos[$nombre] = $numerosValidos;
        }

        $estadistica = new EstadisticaBasica();
        $informe = $estadistica->generalInforme($conjuntos);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Estadística Básica</title>
    <!-- Carga de Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Carga de Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Enlace a tu archivo CSS externo -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-start min-vh-100 p-4">
    <div class="container bg-white shadow-lg rounded-lg p-4">
        <h1 class="text-center mb-4 text-primary">Calculadora de Estadística Básica</h1>
        <p class="text-center text-muted mb-5">
            Introduce conjuntos de números para calcular su media, mediana y moda.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" id="mainForm">
            <div id="conjuntos-container">
                <?php
                // Si hay datos de conjuntos después de un POST (ej. por error), rellenar el formulario
                if (!empty($conjuntosInput)) {
                    foreach ($conjuntosInput as $index => $c) {
                        $nombre = htmlspecialchars($c['nombre'] ?? '');
                        $valores = htmlspecialchars($c['valores'] ?? '');
                        
                        // Determinar si los campos deben ser marcados como inválidos por PHP
                        $is_invalid_name = empty($nombre) ? 'is-invalid' : '';
                        $is_invalid_values = '';
                        try {
                            // Intentar parsear y validar los valores para aplicar 'is-invalid' si no son válidos
                            // La limpieza aquí es para la validación de PHP, JS maneja la del cliente
                            $temp_valores_str_cleaned = preg_replace('/\s+/', ',', $valores); // Convertir espacios a comas para PHP
                            $temp_valores_str_cleaned = preg_replace('/,\s*,/', ',', $temp_valores_str_cleaned);
                            $temp_valores_str_cleaned = trim($temp_valores_str_cleaned, ' ,');
                            $temp_valores_str_cleaned = preg_replace('/\s*,\s*/', ',', $temp_valores_str_cleaned);
                            
                            $temp_valores_array = explode(',', $temp_valores_str_cleaned);
                            $temp_valores_array = array_map('trim', $temp_valores_array);
                            $temp_valores_array = array_filter($temp_valores_array, 'strlen');

                            if (empty($temp_valores_array)) {
                                $is_invalid_values = 'is-invalid';
                            } else {
                                foreach($temp_valores_array as $val) {
                                    if (!is_numeric($val)) {
                                        $is_invalid_values = 'is-invalid';
                                        break;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                             $is_invalid_values = 'is-invalid';
                        }

                        // Determinar si el campo debe ser 'is-valid' (solo si no es 'is-invalid' y no está vacío)
                        $is_valid_name = empty($is_invalid_name) && !empty($nombre) ? 'is-valid' : '';
                        $is_valid_values = empty($is_invalid_values) && !empty($valores) ? 'is-valid' : '';
                        ?>
                        <div class="conjunto-card">
                            <button type="button" class="btn btn-danger btn-sm btn-remove">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <div class="mb-3">
                                <label class="form-label">Nombre del Conjunto</label>
                                <input type="text" name="conjuntos[<?= $index ?>][nombre]" class="form-control <?= $is_invalid_name ?> <?= $is_valid_name ?>" value="<?= $nombre ?>" required>
                                <div class="invalid-feedback">El nombre no puede estar vacío.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Valores</label>
                                <div class="input-group has-validation">
                                    <input type="text" name="conjuntos[<?= $index ?>][valores]" class="form-control form-control-comma <?= $is_invalid_values ?> <?= $is_valid_values ?>" value="<?= $valores ?>" required
                                           placeholder="Ej: 5, 7.2, 10, 8.5">
                                    <!-- Eliminado: <div class="input-group-text input-group-text-validation d-none"></div> -->
                                    <div class="form-text text-muted">  Ingrese números separados por comas o espacios.</div>
                                    <div class="invalid-feedback">  Ingrese números válidos separados por comas o espacios.</div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Si no hay datos POST, el JavaScript añadirá el primer conjunto
                }
                ?>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-between w-100 mt-4">
                <button type="button" id="btn-add-conjunto" class="btn btn-primary btn-lg flex-grow-1 rounded-lg shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i>Añadir Conjunto
                </button>
                <button type="submit" id="btn-calculate" class="btn btn-success btn-lg flex-grow-1 rounded-lg shadow-sm">
                    <i class="fas fa-calculator me-2"></i>Calcular Estadísticas
                </button>
            </div>
        </form>

        <?php if (!empty($informe)): ?>
            <div class="card mt-5" id="resultsCard">
                <div class="card-header">
                    Resultados del Análisis Estadístico
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="resultsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Conjunto</th>
                                    <th>Media</th>
                                    <th>Mediana</th>
                                    <th>Moda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($informe as $nombre => $metricas): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($nombre) ?></td>
                                        <?php if (isset($metricas['error'])): ?>
                                            <td colspan="3" class="text-danger">
                                                <?= htmlspecialchars($metricas['error']) ?>
                                            </td>
                                        <?php else: ?>
                                            <td><?= htmlspecialchars($metricas['media']) ?></td>
                                            <td><?= htmlspecialchars($metricas['mediana']) ?></td>
                                            <td><?= htmlspecialchars($metricas['moda']) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Modal para mensajes de error o información (reemplaza alert()) -->
        <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="messageModalLabel">Información</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="messageModalBody">
                        <!-- El mensaje se insertará aquí -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Plantilla para nuevos conjuntos (usada por JavaScript) -->
    <template id="conjunto-template">
        <div class="conjunto-card">
            <button type="button" class="btn btn-danger btn-sm btn-remove">
                <i class="fas fa-trash-alt"></i>
            </button>
            <div class="mb-3">
                <label class="form-label">Nombre del Conjunto</label>
                <!-- El name se actualizará dinámicamente con JS para asegurar índices correctos -->
                <input type="text" name="conjuntos[INDEX_PLACEHOLDER][nombre]" class="form-control" required>
                <div class="invalid-feedback">El nombre no puede estar vacío.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Valores</label>
                <div class="input-group has-validation">
                    <!-- El name se actualizará dinámicamente con JS para asegurar índices correctos -->
                    <input type="text" name="conjuntos[INDEX_PLACEHOLDER][valores]" class="form-control form-control-comma" required
                           placeholder="Ej: 5, 7.2, 10, 8.5">
                    <!-- Eliminado: <div class="input-group-text input-group-text-validation d-none"></div> -->
                    <div class="form-text text-muted">  Ingrese números separados por comas o espacios.</div>
                    <div class="invalid-feedback">  Ingrese números válidos separados por comas o espacios.</div>
                </div>
            </div>
        </div>
    </template>

    <!-- Carga de Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <!-- Enlace a tu archivo JavaScript externo -->
    <script src="script.js"></script>
</body>
</html>