// Script para controlar a exibição do menu lateral
const toggleButton = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const content = document.querySelector('.content');

// Função para alternar a visibilidade do menu lateral
const toggleSidebar = () => {
    sidebar.classList.toggle('show');
    content.classList.toggle('shift');
};

// Evento para abrir ou fechar o menu lateral ao clicar no botão
toggleButton.addEventListener('click', toggleSidebar);

// Função para fechar o menu lateral se clicar fora dele
const closeSidebarOnClickOutside = (event) => {
    if (!sidebar.contains(event.target) && !toggleButton.contains(event.target)) {
        sidebar.classList.remove('show');
        content.classList.remove('shift');
    }
};

// Evento para fechar o menu lateral ao clicar fora dele
document.addEventListener('click', closeSidebarOnClickOutside);

// Opcional: Fechar o menu lateral ao pressionar a tecla "Esc"
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
        content.classList.remove('shift');
    }
});
