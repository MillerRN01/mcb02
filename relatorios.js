document.addEventListener('DOMContentLoaded', () => {
    initializeDateRange();
    initializeCharts();
    loadReportData();
});

// Inicialização de Datas
function initializeDateRange() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    setDateInputs(thirtyDaysAgo, today);

    // Event listeners para atualização automática
    ['startDate', 'endDate'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadReportData);
    });
}

function setDateInputs(startDate, endDate) {
    document.getElementById('startDate').value = formatDate(startDate);
    document.getElementById('endDate').value = formatDate(endDate);
}

// Configuração de Períodos
function setDateRange(period) {
    const endDate = new Date();
    const startDate = new Date();

    switch (period) {
        case 'hoje':
            startDate.setHours(0, 0, 0, 0);
            break;
        case 'semana':
            startDate.setDate(startDate.getDate() - 7);
            break;
        case 'mes':
            startDate.setMonth(startDate.getMonth() - 1);
            break;
        case 'ano':
            startDate.setFullYear(startDate.getFullYear() - 1);
            break;
    }

    setDateInputs(startDate, endDate);
    loadReportData();
}

// Inicialização dos Gráficos
function initializeCharts() {
    window.vendasChart = createChart('vendasChart', 'line', {
        labels: [],
        datasets: [{
            label: 'Vendas',
            data: [],
            borderColor: '#4CAF50',
            tension: 0.1
        }]
    });

    window.produtosChart = createChart('produtosChart', 'bar', {
        labels: [],
        datasets: [{
            label: 'Quantidade Vendida',
            data: [],
            backgroundColor: '#2196F3'
        }]
    });

    window.clientesChart = createChart('clientesChart', 'pie', {
        labels: ['Novos', 'Recorrentes', 'Inativos'],
        datasets: [{
            data: [30, 50, 20],
            backgroundColor: ['#4CAF50', '#2196F3', '#F44336']
        }]
    });

    window.financeiroChart = createChart('financeiroChart', 'bar', {
        labels: [],
        datasets: [{
            label: 'Receitas',
            data: [],
            backgroundColor: '#4CAF50'
        }, {
            label: 'Despesas',
            data: [],
            backgroundColor: '#F44336'
        }]
    });
}

function createChart(canvasId, type, data) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: type !== 'pie' // Exibe legenda apenas para gráficos que não são de pizza
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Carregamento de Dados
async function loadReportData() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    try {
        const data = await fetchReportData(startDate, endDate);
        updateCharts(data);
        updateMetrics(data);
        updateTables(data);
    } catch (error) {
        showError('Erro ao carregar dados dos relatórios: ' + error.message);
    }
}

// Simulação de dados
async function fetchReportData(startDate, endDate) {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                vendas: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    data: [30000, 45000, 35000, 50000, 48000, 55000],
                    metrics: {
                        total: 45678.90,
                        ticketMedio: 150.00,
                        totalPedidos: 304,
                        crescimento: 12.5
                    }
                },
                produtos: {
                    labels: ['Produto A', 'Produto B', 'Produto C', 'Produto D', 'Produto E'],
                    data: [120, 98, 85, 75, 65],
                    topProdutos: [
                        { nome: 'Produto A', quantidade: 120, valor: 12000, percentual: 25 },
                        { nome: 'Produto B', quantidade: 98, valor: 9800, percentual: 20 },
                        { nome: 'Produto C', quantidade: 85, valor: 8500, percentual: 18 }
                    ]
                },
                financeiro: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                    receitas: [50000, 55000, 48000, 60000, 58000, 65000],
                    despesas: [30000, 32000, 35000, 34000, 36000, 38000]
                }
            });
        }, 500);
    });
}

// Atualização dos Gráficos
function updateCharts(data) {
    updateChart(window.vendasChart, data.vendas.labels, data.vendas.data);
    updateChart(window.produtosChart, data.produtos.labels, data.produtos.data);
    updateChart(window.financeiroChart, data.financeiro.labels, data.financeiro.receitas, data.financeiro.despesas);
}

function updateChart(chart, labels, data, secondData) {
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    if (secondData) {
        chart.data.datasets[1].data = secondData;
    }
    chart.update();
}

// Atualização das Métricas
function updateMetrics(data) {
    document.querySelector('.report-metrics .metric-value').textContent =
        formatCurrency(data.vendas.metrics.total);
}

// Atualização das Tabelas
function updateTables(data) {
    const tbody = document.querySelector('.top-products tbody');
    const fragment = document.createDocumentFragment();

    data.produtos.topProdutos.forEach(produto => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${produto.nome}</td>
            <td>${produto.quantidade}</td>
            <td>${formatCurrency(produto.valor)}</td>
            <td>${produto.percentual}%</td>
        `;
        fragment.appendChild(row);
    });

    tbody.innerHTML = '';
    tbody.appendChild(fragment);
}

// Funções do Modal de Relatório
function generateReport(type) {
    const titles = {
        vendas_detalhado: 'Relatório Detalhado de Vendas',
        estoque: 'Relatório de Controle de Estoque',
        financeiro_completo: 'Relatório Financeiro Completo',
        comissoes: 'Relatório de Comissões'
    };

    document.getElementById('reportTitle').textContent = titles[type];
    document.getElementById('reportModal').style.display = 'block';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

async function downloadReport() {
    const format = document.getElementById('reportFormat').value;
    const grouping = document.getElementById('reportGrouping').value;

    try {
        await new Promise(resolve => setTimeout(resolve, 1000)); // Simulação de geração do relatório
        showSuccess('Relatório gerado com sucesso!');
        closeReportModal();
    } catch (error) {
        showError('Erro ao gerar relatório: ' + error.message);
    }
}

// Funções de Layout
function toggleFullscreen(button) {
    const card = button.closest('.report-card');
    card.classList.toggle('fullscreen');

    const icon = button.querySelector('i');
    icon.classList.replace(card.classList.contains('fullscreen') ? 'fa-expand' : 'fa-compress', 
                          card.classList.contains('fullscreen') ? 'fa-compress' : 'fa-expand');
}

// Funções Utilitárias
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function showSuccess(message) {
    // Implementar notificação mais elegante
    console.log('Sucesso: ' + message);
}

function showError(message) {
    // Implementar notificação mais elegante
    console.error('Erro: ' + message);
}

// Exportação
function exportCurrentReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    // Implementar lógica de exportação
    console.log(`Exportando relatório do período: ${startDate} até ${endDate}`);
}

// Impressão
function printReport() {
    window.print();
}
