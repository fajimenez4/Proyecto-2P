document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('conjuntos-container');
    const template = document.getElementById('conjunto-template');
    const addButton = document.getElementById('btn-add-conjunto');
    const mainForm = document.getElementById('mainForm');
    const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    const messageModalBody = document.getElementById('messageModalBody');
    const messageModalLabel = document.getElementById('messageModalLabel');

    let conjuntoIndex = 0; // Para asignar índices únicos a los campos name

    /**
     * Muestra un modal con un mensaje.
     * @param {string} title - Título del modal.
     * @param {string} body - Contenido del modal.
     * @param {string} type - Tipo de mensaje (e.g., 'info', 'error', 'success').
     */
    function showModal(title, body, type = 'info') {
        const modalHeader = document.querySelector('#messageModal .modal-header');
        messageModalLabel.textContent = title;
        messageModalBody.innerHTML = body;

        modalHeader.classList.remove('bg-primary', 'bg-danger', 'bg-success');
        if (type === 'error') {
            modalHeader.classList.add('bg-danger');
        } else if (type === 'success') {
            modalHeader.classList.add('bg-success');
        } else {
            modalHeader.classList.add('bg-primary');
        }
        messageModal.show();
    }

    /**
     * Valida si una cadena de texto contiene solo números, comas, puntos, guiones y espacios.
     * @param {string} value - La cadena a validar.
     * @returns {boolean} - True si es válida, false en caso contrario.
     */
    function isValidNumericCommaSpaceString(value) {
        // Permite números enteros o decimales, comas, espacios y guiones para números negativos.
        return /^[0-9.,\s-]*$/.test(value);
    }

    /**
     * Limpia y valida los datos de entrada, convirtiéndolos a un array de números.
     * @param {string} rawData - La cadena de datos crudos.
     * @returns {number[]} - Array de números válidos.
     * @throws {Error} Si los datos no son válidos o están vacíos.
     */
    function parseAndValidateData(rawData) {
        // Reemplazar todos los espacios por comas para un parseo consistente
        let cleanedForParse = rawData.replace(/\s+/g, ',');
        // Eliminar comas múltiples y espacios/comas al inicio/final
        cleanedForParse = cleanedForParse.replace(/,+/g, ',');
        cleanedForParse = cleanedForParse.replace(/^\s*,\s*|\s*,\s*$/g, '');

        const dataSegments = cleanedForParse.split(',').filter(s => s !== '');

        if (dataSegments.length === 0) {
            throw new Error("El conjunto de datos no puede estar vacío.");
        }

        const numbers = dataSegments.map(val => {
            const num = parseFloat(val);
            if (isNaN(num)) {
                throw new Error(`'${val}' no es un número válido.`);
            }
            return num;
        });
        return numbers;
    }

    /**
     * Aplica las clases de validación de Bootstrap a un input.
     * @param {HTMLElement} inputElement - El elemento input.
     * @param {boolean} isValid - Si el input es válido.
     * @param {HTMLElement} [validationIconElement] - El elemento del icono de validación (ahora opcional, ya no se usa).
     */
    function applyValidationClasses(inputElement, isValid) { // Eliminado validationIconElement del parámetro
        if (isValid) {
            inputElement.classList.remove('is-invalid');
            inputElement.classList.add('is-valid');
            // Eliminado: validationIconElement.classList.remove('d-none');
            // Eliminado: validationIconElement.innerHTML = '<i class="fas fa-check text-success"></i>';
        } else {
            inputElement.classList.remove('is-valid');
            inputElement.classList.add('is-invalid');
            // Eliminado: validationIconElement.classList.remove('d-none');
            // Eliminado: validationIconElement.innerHTML = '<i class="fas fa-times text-danger"></i>';
        }
    }

    /**
     * Maneja la validación en tiempo real para el input de valores.
     * @param {Event} event - El evento de input.
     */
    function handleValuesInput(event) {
        const input = event.target;
        let value = input.value;
        const oldCursorPosition = input.selectionStart;

        // Eliminado: const validationIcon = input.closest('.input-group').querySelector('.input-group-text-validation');
        const feedbackElement = input.closest('.mb-3').querySelector('.invalid-feedback');
        const helpTextElement = input.closest('.mb-3').querySelector('.form-text'); // El texto de ayuda

        let newValue = value;
        let newCursorPosition = oldCursorPosition;

        // --- Limpieza General del Valor del Input (solo para visualización y validación) ---
        // Reemplazar múltiples espacios por uno solo
        newValue = newValue.replace(/\s{2,}/g, ' ');
        // Reemplazar " , " o ", " por ", " (normalizar espacio después de coma)
        newValue = newValue.replace(/\s*,\s*/g, ', ');
        // Eliminar comas al inicio o al final si no hay números
        newValue = newValue.replace(/^,\s*|\s*,$/g, '');
        // Eliminar comas dobles
        newValue = newValue.replace(/,,/g, ',');


        // Ajustar la posición del cursor si el valor del input cambió debido a la limpieza
        if (input.value !== newValue) {
            const diff = newValue.length - value.length;
            newCursorPosition = Math.max(0, Math.min(newValue.length, oldCursorPosition + diff));
            input.value = newValue;
            input.setSelectionRange(newCursorPosition, newCursorPosition);
        }
        value = input.value; // Usar el valor final para la validación

        // --- Validación ---
        if (value === '') {
            input.classList.remove('is-valid', 'is-invalid');
            // Eliminado: validationIcon.classList.add('d-none');
            if (feedbackElement) feedbackElement.style.display = 'none';
            if (helpTextElement) helpTextElement.style.display = 'block'; // Mostrar texto de ayuda
            return;
        }

        if (isValidNumericCommaSpaceString(value)) {
            try {
                // Intentar parsear para una validación más estricta de los números
                parseAndValidateData(value);
                applyValidationClasses(input, true); // Eliminado validationIcon del parámetro
                if (feedbackElement) feedbackElement.style.display = 'none';
                if (helpTextElement) helpTextElement.style.display = 'none'; // Ocultar texto de ayuda
            } catch (error) {
                applyValidationClasses(input, false); // Eliminado validationIcon del parámetro
                if (feedbackElement) {
                    feedbackElement.textContent = error.message;
                    feedbackElement.style.display = 'block';
                }
                if (helpTextElement) helpTextElement.style.display = 'none'; // Ocultar texto de ayuda
            }
        } else {
            applyValidationClasses(input, false); // Eliminado validationIcon del parámetro
            if (feedbackElement) {
                feedbackElement.textContent = 'Solo se permiten números, comas, puntos y guiones.';
                feedbackElement.style.display = 'block';
            }
            if (helpTextElement) helpTextElement.style.display = 'none'; // Ocultar texto de ayuda
        }
    }

    /**
     * Añade un nuevo conjunto de datos al formulario.
     */
    function addConjunto() {
        const clone = template.content.cloneNode(true);
        const removeBtn = clone.querySelector('.btn-remove');
        const nameInput = clone.querySelector('input[name*="[nombre]"]');
        const valuesInput = clone.querySelector('input[name*="[valores]"]');

        // Asignar índices únicos a los nombres de los campos para PHP
        nameInput.name = `conjuntos[${conjuntoIndex}][nombre]`;
        valuesInput.name = `conjuntos[${conjuntoIndex}][valores]`;
        conjuntoIndex++;

        removeBtn.addEventListener('click', function() {
            if (container.children.length > 1) {
                this.closest('.conjunto-card').remove();
                updateConjuntoIndices(); // Reindexar después de eliminar
            } else {
                showModal('Advertencia', 'Debe haber al menos un conjunto de datos.', 'info');
            }
        });

        // Adjuntar listeners a los nuevos inputs
        valuesInput.addEventListener('input', handleValuesInput);
        nameInput.addEventListener('input', function() {
            const feedbackElement = this.closest('.mb-3').querySelector('.invalid-feedback');
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
                if (feedbackElement) feedbackElement.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid'); // Marcar como válido si no está vacío
                if (feedbackElement) feedbackElement.style.display = 'none';
            }
        });

        container.appendChild(clone);
        // Enfocar el nuevo campo de nombre para facilitar la entrada de datos
        nameInput.focus();
    }

    /**
     * Reindexa los nombres de los campos de los conjuntos después de añadir/eliminar.
     * Esto asegura que PHP reciba un array continuo de conjuntos.
     */
    function updateConjuntoIndices() {
        const conjuntoCards = container.querySelectorAll('.conjunto-card');
        conjuntoCards.forEach((card, index) => {
            card.querySelector('input[name*="[nombre]"]').name = `conjuntos[${index}][nombre]`;
            card.querySelector('input[name*="[valores]"]').name = `conjuntos[${index}][valores]`;
        });
        conjuntoIndex = conjuntoCards.length; // Actualizar el contador del índice
    }

    // Manejar el envío del formulario para la validación final del cliente
    mainForm.addEventListener('submit', function(event) {
        let formIsValid = true;
        const conjuntoCards = container.querySelectorAll('.conjunto-card');

        if (conjuntoCards.length === 0) {
            showModal('Error', 'Debe añadir al menos un conjunto de datos para calcular.', 'error');
            event.preventDefault(); // Detener el envío del formulario
            return;
        }

        conjuntoCards.forEach(card => {
            const nameInput = card.querySelector('input[name*="[nombre]"]');
            const valuesInput = card.querySelector('input[name*="[valores]"]');
            // Eliminado: const valuesValidationIcon = valuesInput.closest('.input-group').querySelector('.input-group-text-validation');
            const nameFeedbackElement = nameInput.closest('.mb-3').querySelector('.invalid-feedback');
            const valuesFeedbackElement = valuesInput.closest('.mb-3').querySelector('.invalid-feedback');

            // Validar nombre
            if (nameInput.value.trim() === '') {
                nameInput.classList.add('is-invalid');
                if (nameFeedbackElement) nameFeedbackElement.style.display = 'block';
                formIsValid = false;
            } else {
                nameInput.classList.remove('is-invalid');
                nameInput.classList.add('is-valid');
                if (nameFeedbackElement) nameFeedbackElement.style.display = 'none';
            }

            // Validar valores
            try {
                parseAndValidateData(valuesInput.value.trim());
                applyValidationClasses(valuesInput, true); // Eliminado valuesValidationIcon del parámetro
                if (valuesFeedbackElement) valuesFeedbackElement.style.display = 'none';
            } catch (error) {
                applyValidationClasses(valuesInput, false); // Eliminado valuesValidationIcon del parámetro
                if (valuesFeedbackElement) {
                    valuesFeedbackElement.textContent = error.message;
                    valuesFeedbackElement.style.display = 'block';
                }
                formIsValid = false;
            }
        });

        // Si la validación del cliente falla, detener el envío
        if (!formIsValid) {
            event.preventDefault();
            showModal('Error de Validación', 'Por favor, corrige los errores en los campos marcados.', 'error');
        }
    });

    // Lógica de inicialización:
    // Si PHP ya ha renderizado conjuntos (debido a un POST previo con errores),
    // re-activar los listeners y la validación para esos campos.
    // Si no hay conjuntos renderizados por PHP, añadir el primer conjunto vacío.
    const initialConjuntoCards = container.querySelectorAll('.conjunto-card');
    if (initialConjuntoCards.length > 0) {
        conjuntoIndex = initialConjuntoCards.length;
        initialConjuntoCards.forEach(card => {
            const removeBtn = card.querySelector('.btn-remove');
            const nameInput = card.querySelector('input[name*="[nombre]"]');
            const valuesInput = card.querySelector('input[name*="[valores]"]');
            // Eliminado: const valuesValidationIcon = valuesInput.closest('.input-group').querySelector('.input-group-text-validation');
            const nameFeedbackElement = nameInput.closest('.mb-3').querySelector('.invalid-feedback');
            const valuesFeedbackElement = valuesInput.closest('.mb-3').querySelector('.invalid-feedback');
            const valuesHelpTextElement = valuesInput.closest('.mb-3').querySelector('.form-text');

            removeBtn.addEventListener('click', function() {
                if (container.children.length > 1) {
                    this.closest('.conjunto-card').remove();
                    updateConjuntoIndices();
                } else {
                    showModal('Advertencia', 'Debe haber al menos un conjunto de datos.', 'info');
                }
            });
            valuesInput.addEventListener('input', handleValuesInput);
            nameInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    this.classList.add('is-invalid');
                    if (nameFeedbackElement) nameFeedbackElement.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    if (nameFeedbackElement) nameFeedbackElement.style.display = 'none';
                }
            });

            // Aplicar validación inicial visualmente (si el campo tiene valor o está marcado por PHP)
            if (nameInput.value.trim() === '') {
                nameInput.classList.add('is-invalid');
                if (nameFeedbackElement) nameFeedbackElement.style.display = 'block';
            } else {
                nameInput.classList.add('is-valid');
            }

            // Re-validar el campo de valores al cargar la página si ya tiene contenido
            if (valuesInput.value.trim() !== '') {
                handleValuesInput({ target: valuesInput }); // Simular un evento input para re-validar
            } else if (valuesInput.classList.contains('is-invalid')) {
                // Si PHP ya lo marcó como inválido y está vacío, mostrar feedback de vacío
                if (valuesFeedbackElement) {
                    valuesFeedbackElement.textContent = 'Los valores no pueden estar vacíos.';
                    valuesFeedbackElement.style.display = 'block';
                }
                if (valuesHelpTextElement) valuesHelpTextElement.style.display = 'none'; // Ocultar texto de ayuda
            }
        });
    } else {
        addConjunto(); // Añadir el primer conjunto si no hay ninguno renderizado por PHP
    }

    // Listener para el botón de añadir conjunto
    addButton.addEventListener('click', addConjunto);
});