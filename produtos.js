class ProductManager {
    constructor() {
        this.initializeComponents();
        this.setupEventListeners();
    }

    initializeComponents() {
        // Inicializa componentes Bootstrap
        this.modal = new bootstrap.Modal(document.getElementById('productModal'));
        this.setupTooltips();
        this.setupFileUploads();
    }

    setupEventListeners() {
        // Event listeners para busca e filtros
        document.getElementById('searchProduct')?.addEventListener('keypress', this.handleSearchKeyPress.bind(this));
        document.getElementById('categoryFilter')?.addEventListener('change', this.handleFilterChange.bind(this));
        document.getElementById('statusFilter')?.addEventListener('change', this.handleFilterChange.bind(this));

        // Event listener para o formulário
        document.getElementById('productForm')?.addEventListener('submit', this.handleProductSubmit.bind(this));

        // Event listeners para preview de imagens
        this.setupImagePreviews();
    }

    setupTooltips() {
        // Inicializa todos os tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    setupFileUploads() {
        // Setup para uploads de arquivos
        ['productPhoto', 'receiptPhoto'].forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('change', (e) => this.handleFileSelect(e, `${id}Preview`));
            }
        });
    }

    setupImagePreviews() {
        // Setup para preview de imagens
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const previewId = e.target.id + 'Preview';
                this.handleFileSelect(e, previewId);
            });
        });
    }

    handleFileSelect(event, previewId) {
        const file = event.target.files[0];
        const preview = document.getElementById(previewId);
        
        if (file && preview) {
            // Validação do tipo de arquivo
            if (!file.type.startsWith('image/')) {
                this.showNotification('Por favor, selecione apenas arquivos de imagem.', 'error');
                event.target.value = '';
                return;
            }

            // Validação do tamanho do arquivo (5MB)
            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('O arquivo deve ter menos que 5MB.', 'error');
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-fluid">`;
            };
            reader.readAsDataURL(file);
        }
    }

    async handleProductSubmit(event) {
        event.preventDefault();
        
        try {
            const form = event.target;
            if (!this.validateForm(form)) {
                return;
            }

            const formData = new FormData(form);
            const response = await this.submitProduct(formData);

            if (response.success) {
                this.showNotification('Produto salvo com sucesso!', 'success');
                this.modal.hide();
                await this.refreshProductList();
            } else {
                throw new Error(response.message || 'Erro ao salvar o produto');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Erro:', error);
        }
    }

    validateForm(form) {
        // Validação personalizada do formulário
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Este campo é obrigatório');
                isValid = false;
            } else {
                this.clearFieldError(field);
            }
        });

        // Validação do preço
        const priceField = form.querySelector('#price');
        if (priceField && parseFloat(priceField.value) <= 0) {
            this.showFieldError(priceField, 'O preço deve ser maior que zero');
            isValid = false;
        }

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    async submitProduct(formData) {
        const response = await fetch('cadastro_produto_conn.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async refreshProductList() {
        const searchTerm = document.getElementById('searchProduct').value;
        const categoryFilter = document.getElementById('categoryFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        const params = new URLSearchParams({
            search: searchTerm,
            category: categoryFilter,
            status: statusFilter
        });

        window.location.href = `cadastro_produto.php?${params.toString()}`;
    }

    handleSearchKeyPress(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            this.refreshProductList();
        }
    }

    handleFilterChange() {
        this.refreshProductList();
    }

    showNotification(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toastContainer.removeChild(toast);
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    static formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    static async deleteProduct(id) {
        if (!confirm('Tem certeza que deseja excluir este produto?')) {
            return;
        }

        try {
            const response = await fetch('delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Produto excluído com sucesso!', 'success');
                await this.refreshProductList();
            } else {
                throw new Error(data.message || 'Erro ao excluir o produto');
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Erro:', error);
        }
    }
}

// Inicializa o gerenciador quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.productManager = new ProductManager();
});