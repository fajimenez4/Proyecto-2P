document.addEventListener('DOMContentLoaded', function() {
    const p1Container = document.getElementById('polinomio1-terms-container');
    const p2Container = document.getElementById('polinomio2-terms-container');
    const addTermP1Button = document.getElementById('add-term-p1');
    const addTermP2Button = document.getElementById('add-term-p2');
    const termTemplate = document.getElementById('polinomio-term-template');
    const polynomialForm = document.getElementById('polynomialForm');
    const xEvaluarInput = document.getElementById('x_evaluar');

    let p1TermIndex = p1Container.querySelectorAll('.polinomio-term-row').length;
    let p2TermIndex = p2Container.querySelectorAll('.polinomio-term-row').length;

    function updateRemoveButtonVisibility(container) {
        const removeButtons = container.querySelectorAll('.btn-remove-term');
        if (removeButtons.length <= 1) {
            removeButtons.forEach(btn => btn.style.display = 'none');
        } else {
            removeButtons.forEach(btn => btn.style.display = 'block');
        }
    }

    function reindexTerms(container, baseName) {
        const termRows = container.querySelectorAll('.polinomio-term-row');
        termRows.forEach((row, index) => {
            row.querySelector('input[name$="[grado]"]').name = `${baseName}[${index}][grado]`;
            row.querySelector('input[name$="[coeficiente]"]').name = `${baseName}[${index}][coeficiente]`;
            const removeBtn = row.querySelector('.btn-remove-term');
            if (removeBtn) {
                removeBtn.dataset.polinomio = baseName.replace('polinomio', '');
            }
        });
        if (baseName === 'polinomio1') {
            p1TermIndex = termRows.length;
        } else {
            p2TermIndex = termRows.length;
        }
        updateRemoveButtonVisibility(container);
    }

    function handleNumericInputValidation(event) {
        const input = event.target;
        const value = input.value.trim();
        const feedbackElement = input.nextElementSibling;

        if (value === '') {
            input.classList.remove('is-valid', 'is-invalid');
            if (feedbackElement) feedbackElement.style.display = 'none';
        } else if (isNaN(parseFloat(value)) || !isFinite(value)) {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if (feedbackElement) feedbackElement.style.display = 'block';
            if (input.type === 'number' && input.step === '1' && value.includes('.')) {
                 feedbackElement.textContent = 'Debe ser un número entero.';
            } else {
                 feedbackElement.textContent = 'Debe ser un número válido.';
            }
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (feedbackElement) feedbackElement.style.display = 'none';
        }
    }

    function addTerm(container, baseName, currentIndex) {
        const clone = termTemplate.content.cloneNode(true);
        const gradoInput = clone.querySelector('input[name*="[grado]"]');
        const coeficienteInput = clone.querySelector('input[name*="[coeficiente]"]');
        const removeBtn = clone.querySelector('.btn-remove-term');

        gradoInput.name = `${baseName}[${currentIndex}][grado]`;
        coeficienteInput.name = `${baseName}[${currentIndex}][coeficiente]`;
        removeBtn.dataset.polinomio = baseName.replace('polinomio', ''); 

        gradoInput.addEventListener('input', handleNumericInputValidation);
        coeficienteInput.addEventListener('input', handleNumericInputValidation);
        
        removeBtn.addEventListener('click', function() {

            if (container.querySelectorAll('.polinomio-term-row').length > 1) {
                this.closest('.polinomio-term-row').remove();
                reindexTerms(container, baseName);
            } else {
                alert('Un polinomio debe tener al menos un término.');
            }
        });

        container.appendChild(clone);
        reindexTerms(container, baseName); 
        gradoInput.focus(); 
        return currentIndex + 1;
    }


    addTermP1Button.addEventListener('click', () => {
        p1TermIndex = addTerm(p1Container, 'polinomio1', p1TermIndex);
    });

    addTermP2Button.addEventListener('click', () => {
        p2TermIndex = addTerm(p2Container, 'polinomio2', p2TermIndex);
    });

    p1Container.querySelectorAll('.polinomio-term-row').forEach(row => {
        row.querySelector('input[name$="[grado]"]').addEventListener('input', handleNumericInputValidation);
        row.querySelector('input[name$="[coeficiente]"]').addEventListener('input', handleNumericInputValidation);
        const removeBtn = row.querySelector('.btn-remove-term');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (p1Container.querySelectorAll('.polinomio-term-row').length > 1) {
                    this.closest('.polinomio-term-row').remove();
                    reindexTerms(p1Container, 'polinomio1');
                } else {
                    alert('Un polinomio debe tener al menos un término.');
                }
            });
        }
    });

    p2Container.querySelectorAll('.polinomio-term-row').forEach(row => {
        row.querySelector('input[name$="[grado]"]').addEventListener('input', handleNumericInputValidation);
        row.querySelector('input[name$="[coeficiente]"]').addEventListener('input', handleNumericInputValidation);
        const removeBtn = row.querySelector('.btn-remove-term');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (p2Container.querySelectorAll('.polinomio-term-row').length > 1) {
                    this.closest('.polinomio-term-row').remove();
                    reindexTerms(p2Container, 'polinomio2');
                } else {
                    alert('Un polinomio debe tener al menos un término.');
                }
            });
        }
    });

    updateRemoveButtonVisibility(p1Container);
    updateRemoveButtonVisibility(p2Container);

    xEvaluarInput.addEventListener('input', handleNumericInputValidation);

    polynomialForm.addEventListener('submit', function(event) {
        let formIsValid = true;

        const validatePolynomialTerms = (container) => {
            const termRows = container.querySelectorAll('.polinomio-term-row');
            if (termRows.length === 0) {
                formIsValid = false;
                return;
            }
            termRows.forEach(row => {
                const gradoInput = row.querySelector('input[name$="[grado]"]');
                const coeficienteInput = row.querySelector('input[name$="[coeficiente]"]');

                handleNumericInputValidation({ target: gradoInput });
                handleNumericInputValidation({ target: coeficienteInput });

                if (gradoInput.classList.contains('is-invalid') || coeficienteInput.classList.contains('is-invalid')) {
                    formIsValid = false;
                }
                 if (gradoInput.value.trim() === '' || coeficienteInput.value.trim() === '') {
                    gradoInput.classList.add('is-invalid');
                    coeficienteInput.classList.add('is-invalid');
                    formIsValid = false;
                }
            });
        };

        validatePolynomialTerms(p1Container);
        validatePolynomialTerms(p2Container);
        handleNumericInputValidation({ target: xEvaluarInput }); 

        if (!formIsValid) {
            event.preventDefault(); 
            alert('Por favor, corrige los errores en los campos marcados.');
        }
    });
});