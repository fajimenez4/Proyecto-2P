<?php
// Definición de la clase abstracta y la clase Polinomio
abstract class PolinomioAbstracto {
    abstract public function evaluar(float $x): float;
    abstract public function derivada(): array;
}

class Polinomio extends PolinomioAbstracto {
    private array $terminos;

    public function __construct(array $terminos) {
        $this->terminos = $this->limpiarPolinomio($terminos);
    }

    private function limpiarPolinomio(array $terminos_raw): array {
        $limpio = [];
        foreach ($terminos_raw as $grado => $coeficiente) {
            $grado = (int) $grado;
            $coeficiente = (float) $coeficiente;

            // Solo añadir si el coeficiente no es cero, o si es el término constante (grado 0)
            if ($coeficiente !== 0.0 || $grado === 0) {
                // Sumar coeficientes si el grado ya existe (importante para input de usuario)
                $limpio[$grado] = ($limpio[$grado] ?? 0.0) + $coeficiente;
            }
        }

        // Una segunda pasada para eliminar grados con coeficientes que se anularon
        foreach ($limpio as $grado => $coeficiente) {
            if ($coeficiente === 0.0 && $grado !== 0) {
                unset($limpio[$grado]);
            }
        }
        
        krsort($limpio); // Ordenar por grado descendente
        return $limpio;
    }

    public function getTerminos(): array {
        return $this->terminos;
    }

    public function evaluar(float $x): float {
        $resultado = 0.0;
        foreach ($this->terminos as $grado => $coeficiente) {
            $resultado += $coeficiente * ($x ** $grado);
        }
        return $resultado;
    }

    public function derivada(): array {
        $derivada_terminos = [];
        foreach ($this->terminos as $grado => $coeficiente) {
            if ($grado > 0) {
                $nuevo_grado = $grado - 1;
                $nuevo_coeficiente = $coeficiente * $grado;
                // Incluir el término solo si el nuevo coeficiente no es cero, o si es el término constante (grado 0)
                if ($nuevo_coeficiente !== 0.0 || $nuevo_grado === 0) {
                     $derivada_terminos[$nuevo_grado] = $nuevo_coeficiente;
                }
            }
        }

        krsort($derivada_terminos);
        return $derivada_terminos;
    }
}

// Función auxiliar para sumar polinomios (adaptada para PHP web)
function sumarPolinomios(array $polinomio1_terminos, array $polinomio2_terminos): array {
    $suma_terminos = [];
    foreach ($polinomio1_terminos as $grado => $coeficiente) {
        $suma_terminos[$grado] = ($suma_terminos[$grado] ?? 0.0) + $coeficiente;
    }
    foreach ($polinomio2_terminos as $grado => $coeficiente) {
        $suma_terminos[$grado] = ($suma_terminos[$grado] ?? 0.0) + $coeficiente;
    }
    
    // Limpieza de términos de la suma
    $polinomio_suma_limpio = [];
    foreach ($suma_terminos as $grado => $coeficiente) {
        if ($coeficiente !== 0.0 || $grado === 0) {
            $polinomio_suma_limpio[$grado] = $coeficiente;
        }
    }
    krsort($polinomio_suma_limpio);

    return $polinomio_suma_limpio;
}

// Función auxiliar para imprimir polinomios en formato HTML
function imprimirPolinomioHtml(array $terminos): string {
    if (empty($terminos)) {
        return '0';
    }

    $polinomio_str_parts = [];
    krsort($terminos); // Asegurar que estén ordenados por grado descendente

    $first_term = true;
    foreach ($terminos as $grado => $coeficiente) {
        if ($coeficiente == 0 && $grado != 0) continue; // No mostrar términos con coeficiente 0, a menos que sea el término constante

        $abs_coeficiente = abs($coeficiente);
        $signo = '';

        if (!$first_term) {
            $signo = $coeficiente >= 0 ? '+' : '-';
        } else {
            $signo = $coeficiente >= 0 ? '' : '-'; // Primer término no lleva '+'
        }

        $term_part = '';
        if ($grado === 0) {
            $term_part = (string)$abs_coeficiente;
        } elseif ($grado === 1) {
            $term_part = ($abs_coeficiente == 1 ? '' : (string)$abs_coeficiente) . 'x';
        } else {
            $term_part = ($abs_coeficiente == 1 ? '' : (string)$abs_coeficiente) . 'x<sup>' . $grado . '</sup>';
        }
        
        $polinomio_str_parts[] = $signo . $term_part;
        $first_term = false;
    }

    // Si todos los términos se cancelaron o eran cero, mostrar '0'
    if (empty($polinomio_str_parts)) {
        return '0';
    }

    return implode(' ', $polinomio_str_parts);
}

// --- Lógica para el manejo de la interfaz web ---

$p1_terminos_input = [];
$p2_terminos_input = [];
$x_evaluar_input = '';

$p1_display = '0';
$p2_display = '0';
$suma_display = '0';
$evaluacion_display = 'N/A';
$derivada_display = '0';

$error = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Helper para procesar los términos desde el input
        function processPolinomioInput(array $raw_input): array {
            $processed_terms = [];
            foreach ($raw_input as $term) {
                $grado = (int) ($term['grado'] ?? 0);
                $coeficiente = (float) ($term['coeficiente'] ?? 0.0);

                if (!is_numeric($term['grado']) || !is_numeric($term['coeficiente'])) {
                    throw new InvalidArgumentException("Grado y coeficiente deben ser números.");
                }
                
                // Acumular coeficientes para el mismo grado
                $processed_terms[$grado] = ($processed_terms[$grado] ?? 0.0) + $coeficiente;
            }
            return $processed_terms;
        }

        // Leer datos de los polinomios del POST
        $p1_raw = $_POST['polinomio1'] ?? [];
        $p2_raw = $_POST['polinomio2'] ?? [];
        $x_evaluar_input = $_POST['x_evaluar'] ?? '';

        $p1_terminos_input = $p1_raw; // Para rellenar el formulario
        $p2_terminos_input = $p2_raw; // Para rellenar el formulario

        $terminos_p1 = processPolinomioInput($p1_raw);
        $terminos_p2 = processPolinomioInput($p2_raw);

        // Crear objetos Polinomio
        $p1 = new Polinomio($terminos_p1);
        $p2 = new Polinomio($terminos_p2);

        $p1_display = imprimirPolinomioHtml($p1->getTerminos());
        $p2_display = imprimirPolinomioHtml($p2->getTerminos());

        // Calcular la suma
        $terminos_suma = sumarPolinomios($p1->getTerminos(), $p2->getTerminos());
        $suma_display = imprimirPolinomioHtml($terminos_suma);
        
        // Calcular la derivada de P1
        $terminos_derivada = $p1->derivada();
        $derivada_display = imprimirPolinomioHtml($terminos_derivada);

        // Evaluar P1 si se proporcionó un valor de x
        if (isset($x_evaluar_input) && is_numeric($x_evaluar_input)) {
            $x_evaluar = (float) $x_evaluar_input;
            $resultado_evaluacion = $p1->evaluar($x_evaluar);
            $evaluacion_display = "P1(" . htmlspecialchars($x_evaluar_input) . ") = " . htmlspecialchars($resultado_evaluacion);
        } else if (!empty($x_evaluar_input)) {
            throw new InvalidArgumentException("El valor de 'x' debe ser numérico.");
        }

        $success_message = "Cálculos realizados correctamente.";

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        $error = "Ocurrió un error inesperado: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Polinomios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
    <style>
        .polinomio-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            position: relative;
        }
        .polinomio-term-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .polinomio-term-row:last-child {
            margin-bottom: 0;
        }
        .btn-remove-term {
            flex-shrink: 0;
        }
        .results-box {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        h2 {
            margin-top: 30px;
            margin-bottom: 20px;
            color: #007bff;
        }
    </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-start min-vh-100 p-4">
    <div class="container bg-white shadow-lg rounded-lg p-4 my-5">
        <h1 class="text-center mb-4 text-primary">Calculadora de Polinomios</h1>
        <p class="text-center text-muted mb-5">
            Introduce los términos de dos polinomios para realizar operaciones como suma, evaluación y derivada.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Éxito:</strong> <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" id="polynomialForm">
            <div class="polinomio-card">
                <h4 class="mb-3 text-secondary">Polinomio P1</h4>
                <div id="polinomio1-terms-container">
                    <?php
                    // Renderizar términos existentes o un término vacío si no hay datos POST
                    if (!empty($p1_terminos_input)) {
                        foreach ($p1_terminos_input as $index => $term) {
                            $grado = htmlspecialchars($term['grado'] ?? '');
                            $coeficiente = htmlspecialchars($term['coeficiente'] ?? '');
                            $invalid_grado_class = (!is_numeric($grado) && $grado !== '') ? 'is-invalid' : '';
                            $invalid_coef_class = (!is_numeric($coeficiente) && $coeficiente !== '') ? 'is-invalid' : '';
                            ?>
                            <div class="polinomio-term-row">
                                <div class="col">
                                    <label class="form-label visually-hidden">Grado</label>
                                    <input type="number" step="1" name="polinomio1[<?= $index ?>][grado]" class="form-control form-control-sm <?= $invalid_grado_class ?>" placeholder="Grado" value="<?= $grado ?>" required>
                                    <div class="invalid-feedback">Debe ser un número.</div>
                                </div>
                                <div class="col">
                                    <label class="form-label visually-hidden">Coeficiente</label>
                                    <input type="number" step="any" name="polinomio1[<?= $index ?>][coeficiente]" class="form-control form-control-sm <?= $invalid_coef_class ?>" placeholder="Coeficiente" value="<?= $coeficiente ?>" required>
                                    <div class="invalid-feedback">Debe ser un número.</div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm btn-remove-term" data-polinomio="1">
                                    <i class="fas fa-minus-circle"></i>
                                </button>
                            </div>
                            <?php
                        }
                    } else {
                        // Default: un término vacío
                        ?>
                        <div class="polinomio-term-row">
                            <div class="col">
                                <label class="form-label visually-hidden">Grado</label>
                                <input type="number" step="1" name="polinomio1[0][grado]" class="form-control form-control-sm" placeholder="Grado" required>
                                <div class="invalid-feedback">Debe ser un número.</div>
                            </div>
                            <div class="col">
                                <label class="form-label visually-hidden">Coeficiente</label>
                                <input type="number" step="any" name="polinomio1[0][coeficiente]" class="form-control form-control-sm" placeholder="Coeficiente" required>
                                <div class="invalid-feedback">Debe ser un número.</div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-term" data-polinomio="1" style="display: none;">
                                <i class="fas fa-minus-circle"></i>
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3" data-polinomio="1" id="add-term-p1">
                    <i class="fas fa-plus-circle me-1"></i>Añadir Término
                </button>
            </div>

            <div class="polinomio-card mt-4">
                <h4 class="mb-3 text-secondary">Polinomio P2</h4>
                <div id="polinomio2-terms-container">
                    <?php
                    // Renderizar términos existentes o un término vacío si no hay datos POST
                    if (!empty($p2_terminos_input)) {
                        foreach ($p2_terminos_input as $index => $term) {
                            $grado = htmlspecialchars($term['grado'] ?? '');
                            $coeficiente = htmlspecialchars($term['coeficiente'] ?? '');
                             $invalid_grado_class = (!is_numeric($grado) && $grado !== '') ? 'is-invalid' : '';
                            $invalid_coef_class = (!is_numeric($coeficiente) && $coeficiente !== '') ? 'is-invalid' : '';
                            ?>
                            <div class="polinomio-term-row">
                                <div class="col">
                                    <label class="form-label visually-hidden">Grado</label>
                                    <input type="number" step="1" name="polinomio2[<?= $index ?>][grado]" class="form-control form-control-sm <?= $invalid_grado_class ?>" placeholder="Grado" value="<?= $grado ?>" required>
                                    <div class="invalid-feedback">Debe ser un número.</div>
                                </div>
                                <div class="col">
                                    <label class="form-label visually-hidden">Coeficiente</label>
                                    <input type="number" step="any" name="polinomio2[<?= $index ?>][coeficiente]" class="form-control form-control-sm <?= $invalid_coef_class ?>" placeholder="Coeficiente" value="<?= $coeficiente ?>" required>
                                    <div class="invalid-feedback">Debe ser un número.</div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm btn-remove-term" data-polinomio="2">
                                    <i class="fas fa-minus-circle"></i>
                                </button>
                            </div>
                            <?php
                        }
                    } else {
                        // Default: un término vacío
                        ?>
                        <div class="polinomio-term-row">
                            <div class="col">
                                <label class="form-label visually-hidden">Grado</label>
                                <input type="number" step="1" name="polinomio2[0][grado]" class="form-control form-control-sm" placeholder="Grado" required>
                                <div class="invalid-feedback">Debe ser un número.</div>
                            </div>
                            <div class="col">
                                <label class="form-label visually-hidden">Coeficiente</label>
                                <input type="number" step="any" name="polinomio2[0][coeficiente]" class="form-control form-control-sm" placeholder="Coeficiente" required>
                                <div class="invalid-feedback">Debe ser un número.</div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm btn-remove-term" data-polinomio="2" style="display: none;">
                                <i class="fas fa-minus-circle"></i>
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-3" data-polinomio="2" id="add-term-p2">
                    <i class="fas fa-plus-circle me-1"></i>Añadir Término
                </button>
            </div>

            <div class="mb-4 mt-4">
                <label for="x_evaluar" class="form-label">Valor de 'x' para evaluar P1:</label>
                <input type="number" step="any" name="x_evaluar" id="x_evaluar" class="form-control" placeholder="Ej: 5.5" value="<?= htmlspecialchars($x_evaluar_input) ?>">
                <div class="form-text text-muted">Opcional. Si se proporciona, P1 se evaluará en este valor.</div>
                <div class="invalid-feedback">Debe ser un número.</div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-between w-100 mt-4">
                <button type="submit" class="btn btn-success btn-lg flex-grow-1 rounded-lg shadow-sm">
                    <i class="fas fa-calculator me-2"></i>Realizar Operaciones
                </button>
            </div>
        </form>

        <h2 class="mt-5 text-primary text-center">Resultados</h2>
        <div class="results-box">
            <p class="lead"><strong>Polinomio P1:</strong> <span id="display-p1"><?= $p1_display ?></span></p>
            <p class="lead"><strong>Polinomio P2:</strong> <span id="display-p2"><?= $p2_display ?></span></p>
            <p class="lead"><strong>Suma (P1 + P2):</strong> <span id="display-suma"><?= $suma_display ?></span></p>
            <p class="lead"><strong>Derivada (P1'):</strong> <span id="display-derivada"><?= $derivada_display ?></span></p>
            <p class="lead"><strong>Evaluación:</strong> <span id="display-evaluacion"><?= $evaluacion_display ?></span></p>
        </div>

        <template id="polinomio-term-template">
            <div class="polinomio-term-row">
                <div class="col">
                    <label class="form-label visually-hidden">Grado</label>
                    <input type="number" step="1" name="polinomioX[INDEX_PLACEHOLDER][grado]" class="form-control form-control-sm" placeholder="Grado" required>
                    <div class="invalid-feedback">Debe ser un número entero.</div>
                </div>
                <div class="col">
                    <label class="form-label visually-hidden">Coeficiente</label>
                    <input type="number" step="any" name="polinomioX[INDEX_PLACEHOLDER][coeficiente]" class="form-control form-control-sm" placeholder="Coeficiente" required>
                    <div class="invalid-feedback">Debe ser un número.</div>
                </div>
                <button type="button" class="btn btn-danger btn-sm btn-remove-term" data-polinomio="X">
                    <i class="fas fa-minus-circle"></i>
                </button>
            </div>
        </template>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="polinomios.js"></script>
</body>
</html>