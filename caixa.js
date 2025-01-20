class CashierManager {
    constructor() {
        this.initializeComponents();
        this.setupEventListeners();
    }

    // Inicialização de componentes
    initializeComponents() {
        this.transactionModal = new bootstrap.Modal(document.getElementById('transactionModal'));
        this.setupTooltips();
        this.setupCurrencyInputs();
    }

    // Configuração dos event listeners
    setupEventListeners() {
        // Form de transação
        const transactionForm = document.getElementById('transactionForm');
        if (transactionForm) {
            transactionForm.addEventListener('submit', (e) => this.handleTransaction(e));
        }

        // Botões de ação
        document.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.closest('[data-action]').dataset.action;
                if (typeof this[action] === 'function') {
                    this[action]();
                }
            });
        });

        // Filtros de entrada
        this.setupInputFilters();
    }

    // Configuração de tooltips
    setupTooltips() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    }

    // Configuração de inputs de moeda
    setupCurrencyInputs() {
        const currencyInputs = document.querySelectorAll('input[data-type="currency"]');
        currencyInputs.forEach(input => {
            input.addEventListener('input', (e) => this.formatCurrencyInput(e.target));
            input.addEventListener('blur', (e) => this.formatCurrencyInput(e.target));
        });
    }

    // Formatação de input de moeda
    formatCurrencyInput(input) {
        let value = input.value.replace(/\D/g, '');
        value = (parseFloat(value) / 100).toFixed(2);
        input.value = value;
    }

    // Configuração de filtros de entrada
    setupInputFilters() {
        const numberInputs = document.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                if (e.target.value < 0) e.target.value = 0;
            });
        });
    }

    // Manipulação de transação
    async handleTransaction(event) {
        event.preventDefault();
        
        try {
            const form = event.target;
            const formData = new FormData(form);
            
            if (!this.validateTransactionForm(formData)) {
                return;
            }

            const transactionData = {
                tipo: formData.get('tipo'),
                valor: parseFloat(formData.get('valor')),
                descricao: formData.get('descricao'),
                metodo: formData.get('metodo')
            };

            await this.saveTransaction(transactionData);
            
            this.showNotification('Transação registrada com sucesso!', 'success');
            this.transactionModal.hide();
            this.refreshPage();
        } catch (error) {
            this.showNotification(error.message, 'error');
            console.error('Erro na transação:', error);
        }
    }

    // Validação do formulário de transação
    validateTransactionForm(formData) {
        const valor = parseFloat(formData.get('valor'));
        if (!valor || valor <= 0) {
            this.showNotification('Insira um valor válido maior que zero', 'warning');
            return false;
        }

        const descricao = formData.get('descricao').trim();
        if (!descricao) {
            this.showNotification('A descrição é obrigatória', 'warning');
            return false;
        }

        return true;
    }

    // Salvar transação
    async saveTransaction(data) {
        try {
            const response = await fetch('api/transactions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Erro ao salvar a transação');
            }

            return await response.json();
        } catch (error) {
            throw new Error('Erro na comunicação com o servidor');
        }
    }

    // Abrir caixa
    async openCashier() {
        try {
            const { value: initialAmount } = await Swal.fire({
                title: 'Abrir Caixa',
                input: 'number',
                inputLabel: 'Valor inicial do caixa',
                inputPlaceholder: 'Digite o valor inicial...',
                showCancelButton: true,
                inputValidator: (value) => {
                    if (!value || value < 0) {
                        return 'Por favor, insira um valor válido!';
                    }
                }
            });

            if (initialAmount) {
                await this.processCashierOperation('open', { initialAmount });
                this.showNotification('Caixa aberto com sucesso!', 'success');
                this.refreshPage();
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    // Fechar caixa
    async closeCashier() {
        try {
            const { value: notes } = await Swal.fire({
                title: 'Fechar Caixa',
                input: 'textarea',
                inputLabel: 'Observações',
                inputPlaceholder: 'Digite suas observações (opcional)...',
                showCancelButton: true
            });

            if (notes !== undefined) {
                await this.processCashierOperation('close', { notes });
                this.showNotification('Caixa fechado com sucesso!', 'success');
                this.refreshPage();
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    // Processar operação do caixa
    async processCashierOperation(operation, data) {
        try {
            const response = await fetch(`api/cashier_${operation}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`Erro ao ${operation === 'open' ? 'abrir' : 'fechar'} o caixa`);
            }

            return await response.json();
        } catch (error) {
            throw new Error('Erro na comunicação com o servidor');
        }
    }

    // Visualizar transação
    async viewTransaction(id) {
        try {
            const transaction = await this.fetchTransaction(id);
            
            await Swal.fire({
                title: 'Detalhes da Transação',
                html: this.generateTransactionDetails(transaction),
                icon: 'info',
                confirmButtonText: 'Fechar'
            });
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    // Buscar transação
    async fetchTransaction(id) {
        try {
            const response = await fetch(`api/transactions.php?id=${id}`);
            if (!response.ok) throw new Error('Erro ao buscar transação');
            return await response.json();
        } catch (error) {
            throw new Error('Erro ao carregar detalhes da transação');
        }
    }

    // Gerar HTML dos detalhes da transação
    generateTransactionDetails(transaction) {
        return `
            <div class="transaction-details">
                <p><strong>Data/Hora:</strong> ${this.formatDateTime(transaction.data_hora)}</p>
                <p><strong>Tipo:</strong> <span class="text-${transaction.tipo === 'entrada' ? 'success' : 'danger'}">
                    ${transaction.tipo === 'entrada' ? 'Entrada' : 'Saída'}
                </span></p>
                <p><strong>Valor:</strong> R$ ${this.formatCurrency(transaction.valor)}</p>
                <p><strong>Descrição:</strong> ${transaction.descricao}</p>
                <p><strong>Método:</strong> ${transaction.metodo_pagamento}</p>
                <p><strong>Operador:</strong> ${transaction.operador}</p>
            </div>
        `;
    }

    // Cancelar transação
    async cancelTransaction(id) {
        try {
            const result = await Swal.fire({
                title: 'Cancelar Transação',
                text: 'Tem certeza que deseja cancelar esta transação?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, cancelar!',
                cancelButtonText: 'Não'
            });

            if (result.isConfirmed) {
                await this.processTransactionCancellation(id);
                this.showNotification('Transação cancelada com sucesso!', 'success');
                this.refreshPage();
            }
        } catch (error) {
            this.showNotification(error.message, 'error');
        }
    }

    // Processar cancelamento de transação
    async processTransactionCancellation(id) {
        try {
            const response = await fetch('api/cancel_transaction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id })
            });

            if (!response.ok) throw new Error('Erro ao cancelar transação');
            return await response.json();
        } catch (error) {
            throw new Error('Erro na comunicação com o servidor');
        }
    }

    // Imprimir relatório diário
    printDailyReport() {
        const printWindow = window.open('', '_blank');
        const content = document.getElementById('printArea').innerHTML;
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Relatório Diário - ${new Date().toLocaleDateString()}</title>
                    <link rel="stylesheet" href="assets/css/print.css">
                </head>
                <body>
                    ${content}
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.print();
    }

    // Exportar transações
    exportTransactions() {
        const table = document.querySelector('.table');
        const rows = Array.from(table.querySelectorAll('tr'));
        
        let csv = 'data:text/csv;charset=utf-8,';
        csv += rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th,td'));
            return cells.map(cell => `"${cell.textContent}"`).join(',');
        }).join('\n');
        
        const encodedUri = encodeURI(csv);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `transacoes_${this.formatDate(new Date())}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Utilitários
    showNotification(message, type) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3000
        });
    }

    formatDateTime(date) {
        return new Date(date).toLocaleString('pt-BR');
    }

    formatDate(date) {
        return date.toLocaleDateString('pt-BR');
    }

    formatCurrency(value) {
        return parseFloat(value).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    refreshPage() {
        window.location.reload();
    }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.cashierManager = new CashierManager();
});