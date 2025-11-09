<?php $render('header'); ?>

<div class="row">
    <div class="col-lg-3">
        <!-- Índice -->
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Índice
                </h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="#visao-geral" class="list-group-item list-group-item-action">Visão Geral</a>
                <a href="#autenticacao" class="list-group-item list-group-item-action">Autenticação</a>
                <a href="#enviar-email" class="list-group-item list-group-item-action">Enviar E-mail</a>
                <a href="#listar-emails" class="list-group-item list-group-item-action">Listar E-mails</a>
                <a href="#exemplos" class="list-group-item list-group-item-action">Exemplos</a>
                <a href="#codigos-erro" class="list-group-item list-group-item-action">Códigos de Erro</a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <!-- Visão Geral -->
        <div class="card mb-4" id="visao-geral">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-book"></i> Visão Geral
                </h5>
            </div>
            <div class="card-body">
                <p>Bem-vindo à documentação da API MailJZTech! Esta API permite que você envie e-mails através de um serviço centralizado e robusto.</p>
                
                <h6 class="mt-4">Características Principais:</h6>
                <ul>
                    <li><strong>E-mail padrão:</strong> Todos os e-mails saem de <code>contato@jztech.com.br</code></li>
                    <li><strong>Remetente personalizável:</strong> O nome do remetente é configurado no seu sistema</li>
                    <li><strong>Suporte a CC e BCC:</strong> Envie para múltiplos destinatários</li>
                    <li><strong>Anexos:</strong> Suporte a múltiplos anexos</li>
                    <li><strong>Histórico:</strong> Todos os e-mails são registrados</li>
                </ul>

                <div class="alert alert-info mt-4">
                    <strong><i class="fas fa-info-circle"></i> Informação:</strong> Sua chave de API é: 
                    <code class="bg-light p-1 rounded"><?php echo htmlspecialchars($chave_api ?? 'sua_chave_aqui'); ?></code>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copiarChave('<?php echo htmlspecialchars($chave_api ?? ''); ?>')">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Autenticação -->
        <div class="card mb-4" id="autenticacao">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock"></i> Autenticação
                </h5>
            </div>
            <div class="card-body">
                <p>Todas as requisições à API devem incluir sua chave de API no header <code>Authorization</code>:</p>
                
                <div class="bg-dark text-light p-3 rounded mb-3">
                    <code>Authorization: Bearer sua_chave_api_aqui</code>
                </div>

                <h6>Exemplo com cURL:</h6>
                <div class="bg-light p-3 rounded">
                    <pre><code>curl -X POST http://api.mailjztech.com/sendEmail \
  -H "Authorization: Bearer sua_chave_api_aqui" \
  -H "Content-Type: application/json" \
  -d '{...}'</code></pre>
                </div>
            </div>
        </div>

        <!-- Enviar E-mail -->
        <div class="card mb-4" id="enviar-email">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-envelope"></i> Enviar E-mail
                </h5>
            </div>
            <div class="card-body">
                <p><strong>POST</strong> <code>/sendEmail</code></p>

                <h6 class="mt-3">Parâmetros:</h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Tipo</th>
                            <th>Obrigatório</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>destinatario</code></td>
                            <td>string</td>
                            <td><span class="badge bg-danger">Sim</span></td>
                            <td>E-mail do destinatário</td>
                        </tr>
                        <tr>
                            <td><code>assunto</code></td>
                            <td>string</td>
                            <td><span class="badge bg-danger">Sim</span></td>
                            <td>Assunto do e-mail</td>
                        </tr>
                        <tr>
                            <td><code>corpo_html</code></td>
                            <td>string</td>
                            <td><span class="badge bg-danger">Sim</span></td>
                            <td>Corpo em HTML</td>
                        </tr>
                        <tr>
                            <td><code>corpo_texto</code></td>
                            <td>string</td>
                            <td><span class="badge bg-success">Não</span></td>
                            <td>Corpo em texto puro (fallback)</td>
                        </tr>
                        <tr>
                            <td><code>cc</code></td>
                            <td>array</td>
                            <td><span class="badge bg-success">Não</span></td>
                            <td>E-mails em cópia</td>
                        </tr>
                        <tr>
                            <td><code>bcc</code></td>
                            <td>array</td>
                            <td><span class="badge bg-success">Não</span></td>
                            <td>E-mails em cópia oculta</td>
                        </tr>
                        <tr>
                            <td><code>anexos</code></td>
                            <td>array</td>
                            <td><span class="badge bg-success">Não</span></td>
                            <td>Array com objetos {nome, caminho}</td>
                        </tr>
                    </tbody>
                </table>

                <h6 class="mt-3">Resposta de Sucesso (200):</h6>
                <div class="bg-light p-3 rounded">
                    <pre><code>{
  "result": {
    "mensagem": "E-mail enviado com sucesso",
    "idemail": 1,
    "status": "enviado"
  },
  "error": false
}</code></pre>
                </div>
            </div>
        </div>

        <!-- Listar E-mails -->
        <div class="card mb-4" id="listar-emails">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Listar E-mails
                </h5>
            </div>
            <div class="card-body">
                <p><strong>GET</strong> <code>/listarEmails?limite=50&pagina=1</code></p>

                <h6 class="mt-3">Parâmetros:</h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Parâmetro</th>
                            <th>Tipo</th>
                            <th>Padrão</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>limite</code></td>
                            <td>integer</td>
                            <td>50</td>
                            <td>Quantidade de registros por página</td>
                        </tr>
                        <tr>
                            <td><code>pagina</code></td>
                            <td>integer</td>
                            <td>1</td>
                            <td>Número da página</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Exemplos -->
        <div class="card mb-4" id="exemplos">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-code"></i> Exemplos
                </h5>
            </div>
            <div class="card-body">
                <h6>1. Enviar E-mail Simples (cURL)</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <pre><code>curl -X POST http://api.mailjztech.com/sendEmail \
  -H "Authorization: Bearer <?php echo htmlspecialchars($chave_api ?? 'sua_chave'); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Bem-vindo!",
    "corpo_html": "&lt;h1&gt;Olá!&lt;/h1&gt;&lt;p&gt;Bem-vindo ao sistema.&lt;/p&gt;"
  }'</code></pre>
                </div>

                <h6>2. Enviar E-mail com CC e BCC (JavaScript)</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <pre><code>fetch('http://api.mailjztech.com/sendEmail', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer <?php echo htmlspecialchars($chave_api ?? 'sua_chave'); ?>',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    destinatario: 'usuario@example.com',
    cc: ['gerente@example.com'],
    bcc: ['arquivo@example.com'],
    assunto: 'Relatório',
    corpo_html: '&lt;h1&gt;Relatório Mensal&lt;/h1&gt;'
  })
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
                </div>

                <h6>3. Enviar E-mail com Anexo (Python)</h6>
                <div class="bg-light p-3 rounded">
                    <pre><code>import requests

headers = {
    'Authorization': 'Bearer <?php echo htmlspecialchars($chave_api ?? 'sua_chave'); ?>',
    'Content-Type': 'application/json'
}

data = {
    'destinatario': 'usuario@example.com',
    'assunto': 'Documento',
    'corpo_html': '&lt;p&gt;Segue em anexo o documento.&lt;/p&gt;',
    'anexos': [
        {
            'nome': 'documento.pdf',
            'caminho': '/path/to/documento.pdf'
        }
    ]
}

response = requests.post('http://api.mailjztech.com/sendEmail', 
                        headers=headers, 
                        json=data)
print(response.json())</code></pre>
                </div>
            </div>
        </div>

        <!-- Códigos de Erro -->
        <div class="card mb-4" id="codigos-erro">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Códigos de Erro
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Solução</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-success">200</span></td>
                            <td>Sucesso</td>
                            <td>E-mail enviado com sucesso</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning">400</span></td>
                            <td>Requisição Inválida</td>
                            <td>Verifique os parâmetros enviados</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">401</span></td>
                            <td>Não Autorizado</td>
                            <td>Verifique sua chave de API</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">404</span></td>
                            <td>Não Encontrado</td>
                            <td>Endpoint ou recurso não existe</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger">500</span></td>
                            <td>Erro Interno</td>
                            <td>Tente novamente mais tarde</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function copiarChave(chave) {
        copyToClipboard(chave, event.target);
    }
</script>

<?php $render('footer'); ?>
