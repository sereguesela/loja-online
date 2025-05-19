// Função para copiar texto para a área de transferência
function copyToClipboard(text, buttonElement) {
    navigator.clipboard.writeText(text).then(function() {
        // Altera o texto do botão temporariamente
        const originalText = buttonElement.innerHTML;
        buttonElement.innerHTML = 'Copiado!';
        buttonElement.classList.remove('btn-primary');
        buttonElement.classList.add('btn-success');
        
        // Restaura o texto original após 2 segundos
        setTimeout(function() {
            buttonElement.innerHTML = originalText;
            buttonElement.classList.remove('btn-success');
            buttonElement.classList.add('btn-primary');
        }, 2000);
    }, function(err) {
        console.error('Erro ao copiar texto: ', err);
        alert('Não foi possível copiar o texto. Por favor, copie manualmente.');
    });
}

// Configura os botões de copiar PIX
document.addEventListener('DOMContentLoaded', function() {
    const copyPixButton = document.getElementById('copy-pix-key');
    if (copyPixButton) {
        const pixKey = document.getElementById('pix-key').textContent;
        copyPixButton.addEventListener('click', function() {
            copyToClipboard(pixKey, copyPixButton);
        });
    }
    
    // Scroll automático para o final do chat
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Previne envio de formulário em branco
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            const messageInput = document.getElementById('message-input');
            if (!messageInput.value.trim()) {
                e.preventDefault();
                alert('Por favor, digite uma mensagem antes de enviar.');
            }
        });
    }
    
    // Destaca o contato ativo no chat
    const contactItems = document.querySelectorAll('.contact-item');
    if (contactItems.length > 0) {
        contactItems.forEach(item => {
            item.addEventListener('click', function() {
                contactItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Confirmação para excluir produto ou categoria
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Preview de imagem ao fazer upload
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.setAttribute('src', this.result);
                    imagePreview.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            }
        });
    }
}); 