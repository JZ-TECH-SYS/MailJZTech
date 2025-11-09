<div class="card mb-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem;">
    <div class="card-header text-white" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 1rem 1rem 0 0;">
        <h4 class="mb-0"><i class="fas fa-paper-plane"></i> Enviar E-mail</h4>
    </div>
    <div class="card-body">
        <h5 class="text-light mb-3"><i class="fas fa-terminal text-success"></i> Endpoint</h5>
        <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; border-left: 4px solid #4facfe;">
            <code style="color: #f92672; font-weight: bold;">POST</code>
            <code style="color: #87ceeb; font-size: 1.1rem; margin-left: 1rem;">/sendEmail</code>
        </div>

        <h5 class="text-light mb-3"><i class="fas fa-list-ul text-warning"></i> Parâmetros</h5>
        <div class="table-responsive mb-4">
            <table class="table table-dark table-hover">
                <thead style="background: rgba(255,255,255,0.1);">
                    <tr>
                        <th>Parâmetro</th>
                        <th>Tipo</th>
                        <th>Obrigatório</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody style="background: rgba(0,0,0,0.3);">
                    <tr>
                        <td><code style="color: #f92672;">destinatario</code></td>
                        <td><span class="badge bg-info">string</span></td>
                        <td><span class="badge bg-danger">Sim</span></td>
                        <td class="text-light-50">Endereço de e-mail do destinatário</td>
                    </tr>
                    <tr>
                        <td><code style="color: #f92672;">assunto</code></td>
                        <td><span class="badge bg-info">string</span></td>
                        <td><span class="badge bg-danger">Sim</span></td>
                        <td class="text-light-50">Assunto do e-mail</td>
                    </tr>
                    <tr>
                        <td><code style="color: #f92672;">corpo_html</code></td>
                        <td><span class="badge bg-info">string</span></td>
                        <td><span class="badge bg-warning">Não</span></td>
                        <td class="text-light-50">Corpo do e-mail em HTML</td>
                    </tr>
                    <tr>
                        <td><code style="color: #f92672;">corpo_texto</code></td>
                        <td><span class="badge bg-info">string</span></td>
                        <td><span class="badge bg-warning">Não</span></td>
                        <td class="text-light-50">Corpo do e-mail em texto puro (fallback)</td>
                    </tr>
                    <tr>
                        <td><code style="color: #f92672;">anexos</code></td>
                        <td><span class="badge bg-primary">array</span></td>
                        <td><span class="badge bg-warning">Não</span></td>
                        <td class="text-light-50">
                            Array de objetos com anexos. Cada anexo pode ter:<br>
                            • <code style="color: #a6e22e;">caminho</code> + nome (opcional) - Arquivo local<br>
                            • <code style="color: #a6e22e;">base64</code> + nome + type (opcional) - Dados codificados<br>
                            • <code style="color: #a6e22e;">url</code> + nome (opcional) + type (opcional) - Link externo
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="text-light mb-4"><i class="fas fa-code text-primary"></i> Exemplos de Uso</h5>

        <!-- TABS INTERATIVAS -->
        <div class="tab-group">
            <div class="language-tabs">
                <button class="tab-button active" data-lang="php">
                    <i class="fab fa-php"></i> PHP
                </button>
                <button class="tab-button" data-lang="javascript">
                    <i class="fab fa-js"></i> JavaScript
                </button>
                <button class="tab-button" data-lang="python">
                    <i class="fab fa-python"></i> Python
                </button>
            </div>

            <!-- PHP TAB -->
            <div class="tab-content active" data-lang="php">
                <span class="language-badge php">PHP</span>
                <h6 class="text-light mb-3 mt-2"><i class="fas fa-envelope"></i> 1. E-mail Simples</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">&lt;?php</span>
<span style="color: #66d9ef;">$ch</span> = <span style="color: #a6e22e;">curl_init</span>(<span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>);

<span style="color: #a6e22e;">curl_setopt_array</span>(<span style="color: #66d9ef;">$ch</span>, [
    <span style="color: #ae81ff;">CURLOPT_RETURNTRANSFER</span> => <span style="color: #ae81ff;">true</span>,
    <span style="color: #ae81ff;">CURLOPT_POST</span> => <span style="color: #ae81ff;">true</span>,
    <span style="color: #ae81ff;">CURLOPT_HTTPHEADER</span> => [
        <span style="color: #e6db74;">'Authorization: Bearer SUA_CHAVE_API'</span>,
        <span style="color: #e6db74;">'Content-Type: application/json'</span>
    ],
    <span style="color: #ae81ff;">CURLOPT_POSTFIELDS</span> => <span style="color: #a6e22e;">json_encode</span>([
        <span style="color: #e6db74;">'destinatario'</span> => <span style="color: #e6db74;">'cliente@example.com'</span>,
        <span style="color: #e6db74;">'assunto'</span> => <span style="color: #e6db74;">'Bem-vindo!'</span>,
        <span style="color: #e6db74;">'corpo_html'</span> => <span style="color: #e6db74;">'&lt;h1&gt;Olá!&lt;/h1&gt;&lt;p&gt;Obrigado por se cadastrar.&lt;/p&gt;'</span>
    ])
]);

<span style="color: #66d9ef;">$response</span> = <span style="color: #a6e22e;">curl_exec</span>(<span style="color: #66d9ef;">$ch</span>);
<span style="color: #66d9ef;">$result</span> = <span style="color: #a6e22e;">json_decode</span>(<span style="color: #66d9ef;">$response</span>, <span style="color: #ae81ff;">true</span>);

<span style="color: #a6e22e;">curl_close</span>(<span style="color: #66d9ef;">$ch</span>);

<span style="color: #f92672;">if</span> (<span style="color: #66d9ef;">$result</span>[<span style="color: #e6db74;">'error'</span>] === <span style="color: #ae81ff;">false</span>) {
    <span style="color: #a6e22e;">echo</span> <span style="color: #e6db74;">"E-mail enviado com sucesso!"</span>;
} <span style="color: #f92672;">else</span> {
    <span style="color: #a6e22e;">echo</span> <span style="color: #e6db74;">"Erro: "</span> . <span style="color: #66d9ef;">$result</span>[<span style="color: #e6db74;">'result'</span>];
}
<span style="color: #f92672;">?&gt;</span></code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-paperclip"></i> 2. Com Anexo (Caminho Local)</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">&lt;?php</span>
<span style="color: #66d9ef;">$dados</span> = [
    <span style="color: #e6db74;">'destinatario'</span> => <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span> => <span style="color: #e6db74;">'Contrato Anexo'</span>,
    <span style="color: #e6db74;">'corpo_html'</span> => <span style="color: #e6db74;">'&lt;p&gt;Segue o contrato em anexo.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span> => [
        [
            <span style="color: #e6db74;">'caminho'</span> => <span style="color: #e6db74;">'/var/www/uploads/contrato.pdf'</span>,
            <span style="color: #e6db74;">'nome'</span> => <span style="color: #e6db74;">'contrato_2025.pdf'</span>
        ]
    ]
];

<span style="color: #75715e;">// ... mesmo código curl acima com $dados ...</span>
<span style="color: #f92672;">?&gt;</span></code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-file-code"></i> 3. Com Anexo Base64</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">&lt;?php</span>
<span style="color: #66d9ef;">$fileContent</span> = <span style="color: #a6e22e;">file_get_contents</span>(<span style="color: #e6db74;">'/path/to/arquivo.pdf'</span>);
<span style="color: #66d9ef;">$base64</span> = <span style="color: #a6e22e;">base64_encode</span>(<span style="color: #66d9ef;">$fileContent</span>);

<span style="color: #66d9ef;">$dados</span> = [
    <span style="color: #e6db74;">'destinatario'</span> => <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span> => <span style="color: #e6db74;">'Documento Digital'</span>,
    <span style="color: #e6db74;">'corpo_html'</span> => <span style="color: #e6db74;">'&lt;p&gt;Documento enviado digitalmente.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span> => [
        [
            <span style="color: #e6db74;">'base64'</span> => <span style="color: #66d9ef;">$base64</span>,
            <span style="color: #e6db74;">'nome'</span> => <span style="color: #e6db74;">'documento.pdf'</span>,
            <span style="color: #e6db74;">'type'</span> => <span style="color: #e6db74;">'application/pdf'</span>
        ]
    ]
];
<span style="color: #f92672;">?&gt;</span></code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-link"></i> 4. Com Anexo via URL</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">&lt;?php</span>
<span style="color: #66d9ef;">$dados</span> = [
    <span style="color: #e6db74;">'destinatario'</span> => <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span> => <span style="color: #e6db74;">'Catálogo de Produtos'</span>,
    <span style="color: #e6db74;">'corpo_html'</span> => <span style="color: #e6db74;">'&lt;p&gt;Confira nosso catálogo em anexo.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span> => [
        [
            <span style="color: #e6db74;">'url'</span> => <span style="color: #e6db74;">'https://cdn.empresa.com/catalogo_2025.pdf'</span>,
            <span style="color: #e6db74;">'nome'</span> => <span style="color: #e6db74;">'catalogo.pdf'</span>
        ],
        [
            <span style="color: #e6db74;">'url'</span> => <span style="color: #e6db74;">'https://images.empresa.com/logo.png'</span>
            <span style="color: #75715e;">// nome será extraído da URL automaticamente</span>
        ]
    ]
];
<span style="color: #f92672;">?&gt;</span></code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-layer-group"></i> 5. Misturando os 3 Tipos</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">&lt;?php</span>
<span style="color: #66d9ef;">$dados</span> = [
    <span style="color: #e6db74;">'destinatario'</span> => <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span> => <span style="color: #e6db74;">'Proposta Comercial Completa'</span>,
    <span style="color: #e6db74;">'corpo_html'</span> => <span style="color: #e6db74;">'&lt;h1&gt;Proposta&lt;/h1&gt;&lt;p&gt;Todos os documentos em anexo.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span> => [
        [<span style="color: #75715e;">// Arquivo local do servidor</span>
            <span style="color: #e6db74;">'caminho'</span> => <span style="color: #e6db74;">'/var/contratos/contrato_123.pdf'</span>,
            <span style="color: #e6db74;">'nome'</span> => <span style="color: #e6db74;">'contrato.pdf'</span>
        ],
        [<span style="color: #75715e;">// Imagem em base64</span>
            <span style="color: #e6db74;">'base64'</span> => <span style="color: #a6e22e;">base64_encode</span>(<span style="color: #a6e22e;">file_get_contents</span>(<span style="color: #e6db74;">'logo.png'</span>)),
            <span style="color: #e6db74;">'nome'</span> => <span style="color: #e6db74;">'logo.png'</span>,
            <span style="color: #e6db74;">'type'</span> => <span style="color: #e6db74;">'image/png'</span>
        ],
        [<span style="color: #75715e;">// PDF de URL externa</span>
            <span style="color: #e6db74;">'url'</span> => <span style="color: #e6db74;">'https://cdn.empresa.com/catalogo.pdf'</span>
        ]
    ]
];
<span style="color: #f92672;">?&gt;</span></code></pre>
                </div>
            </div>

            <!-- JAVASCRIPT TAB -->
            <div class="tab-content" data-lang="javascript">
                <span class="language-badge javascript">JavaScript</span>
                <h6 class="text-light mb-3 mt-2"><i class="fas fa-envelope"></i> 1. E-mail Simples</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #66d9ef;">const</span> response <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> <span style="color: #a6e22e;">fetch</span>(<span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>, {
    method<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'POST'</span>,
    headers<span style="color: #f92672;">:</span> {
        <span style="color: #e6db74;">'Authorization'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bearer SUA_CHAVE_API'</span>,
        <span style="color: #e6db74;">'Content-Type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/json'</span>
    },
    body<span style="color: #f92672;">:</span> <span style="color: #f92672;">JSON</span>.<span style="color: #a6e22e;">stringify</span>({
        destinatario<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
        assunto<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bem-vindo!'</span>,
        corpo_html<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Olá!&lt;/h1&gt;&lt;p&gt;Obrigado por se cadastrar.&lt;/p&gt;'</span>
    })
});

<span style="color: #66d9ef;">const</span> data <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> response.<span style="color: #a6e22e;">json</span>();

<span style="color: #f92672;">if</span> (data.error <span style="color: #f92672;">===</span> <span style="color: #ae81ff;">false</span>) {
    console.<span style="color: #a6e22e;">log</span>(<span style="color: #e6db74;">'E-mail enviado!'</span>, data.result);
} <span style="color: #f92672;">else</span> {
    console.<span style="color: #a6e22e;">error</span>(<span style="color: #e6db74;">'Erro:'</span>, data.result);
}</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-paperclip"></i> 2. Com Anexo (Upload de Arquivo)</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #75715e;">// HTML: &lt;input type="file" id="arquivo"&gt;</span>

<span style="color: #66d9ef;">const</span> fileInput <span style="color: #f92672;">=</span> document.<span style="color: #a6e22e;">getElementById</span>(<span style="color: #e6db74;">'arquivo'</span>);
<span style="color: #66d9ef;">const</span> file <span style="color: #f92672;">=</span> fileInput.files[<span style="color: #ae81ff;">0</span>];

<span style="color: #75715e;">// Converter para base64</span>
<span style="color: #66d9ef;">const</span> reader <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">new</span> <span style="color: #a6e22e;">FileReader</span>();
reader.<span style="color: #a6e22e;">onload</span> <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">async</span> (<span style="color: #fd971f;">e</span>) <span style="color: #f92672;">=&gt;</span> {
    <span style="color: #66d9ef;">const</span> base64 <span style="color: #f92672;">=</span> e.target.result;
    
    <span style="color: #66d9ef;">const</span> response <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> <span style="color: #a6e22e;">fetch</span>(<span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>, {
        method<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'POST'</span>,
        headers<span style="color: #f92672;">:</span> {
            <span style="color: #e6db74;">'Authorization'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bearer SUA_CHAVE_API'</span>,
            <span style="color: #e6db74;">'Content-Type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/json'</span>
        },
        body<span style="color: #f92672;">:</span> <span style="color: #f92672;">JSON</span>.<span style="color: #a6e22e;">stringify</span>({
            destinatario<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
            assunto<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Documento Enviado'</span>,
            corpo_html<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;p&gt;Segue o documento.&lt;/p&gt;'</span>,
            anexos<span style="color: #f92672;">:</span> [{
                base64<span style="color: #f92672;">:</span> base64,
                nome<span style="color: #f92672;">:</span> file.name,
                type<span style="color: #f92672;">:</span> file.type
            }]
        })
    });
    
    <span style="color: #66d9ef;">const</span> data <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> response.<span style="color: #a6e22e;">json</span>();
    console.<span style="color: #a6e22e;">log</span>(data);
};
reader.<span style="color: #a6e22e;">readAsDataURL</span>(file);</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-link"></i> 3. Com Anexo via URL</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #66d9ef;">const</span> response <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> <span style="color: #a6e22e;">fetch</span>(<span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>, {
    method<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'POST'</span>,
    headers<span style="color: #f92672;">:</span> {
        <span style="color: #e6db74;">'Authorization'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bearer SUA_CHAVE_API'</span>,
        <span style="color: #e6db74;">'Content-Type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/json'</span>
    },
    body<span style="color: #f92672;">:</span> <span style="color: #f92672;">JSON</span>.<span style="color: #a6e22e;">stringify</span>({
        destinatario<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
        assunto<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Documentos Online'</span>,
        corpo_html<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Documentos&lt;/h1&gt;&lt;p&gt;Acesse os anexos.&lt;/p&gt;'</span>,
        anexos<span style="color: #f92672;">:</span> [
            {
                url<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://exemplo.com/relatorio.pdf'</span>,
                nome<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'relatorio.pdf'</span>,
                type<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/pdf'</span>
            },
            {
                url<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://exemplo.com/foto.jpg'</span>
                <span style="color: #75715e;">// nome extraído automaticamente da URL</span>
            }
        ]
    })
});

<span style="color: #66d9ef;">const</span> data <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> response.<span style="color: #a6e22e;">json</span>();
console.<span style="color: #a6e22e;">log</span>(data);</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-layer-group"></i> 4. Exemplo Completo (3 Tipos Misturados)</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #75715e;">// Função auxiliar para converter File para base64</span>
<span style="color: #66d9ef;">async function</span> <span style="color: #a6e22e;">fileToBase64</span>(<span style="color: #fd971f;">file</span>) {
    <span style="color: #f92672;">return</span> <span style="color: #66d9ef;">new</span> <span style="color: #a6e22e;">Promise</span>((<span style="color: #fd971f;">resolve</span>) <span style="color: #f92672;">=&gt;</span> {
        <span style="color: #66d9ef;">const</span> reader <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">new</span> <span style="color: #a6e22e;">FileReader</span>();
        reader.<span style="color: #a6e22e;">onload</span> <span style="color: #f92672;">=</span> () <span style="color: #f92672;">=&gt;</span> <span style="color: #a6e22e;">resolve</span>(reader.result);
        reader.<span style="color: #a6e22e;">readAsDataURL</span>(file);
    });
}

<span style="color: #75715e;">// Exemplo misturando tudo</span>
<span style="color: #66d9ef;">const</span> fileInput <span style="color: #f92672;">=</span> document.<span style="color: #a6e22e;">getElementById</span>(<span style="color: #e6db74;">'arquivo'</span>);
<span style="color: #66d9ef;">const</span> file <span style="color: #f92672;">=</span> fileInput.files[<span style="color: #ae81ff;">0</span>];
<span style="color: #66d9ef;">const</span> base64 <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> <span style="color: #a6e22e;">fileToBase64</span>(file);

<span style="color: #66d9ef;">const</span> response <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> <span style="color: #a6e22e;">fetch</span>(<span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>, {
    method<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'POST'</span>,
    headers<span style="color: #f92672;">:</span> {
        <span style="color: #e6db74;">'Authorization'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bearer SUA_CHAVE_API'</span>,
        <span style="color: #e6db74;">'Content-Type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/json'</span>
    },
    body<span style="color: #f92672;">:</span> <span style="color: #f92672;">JSON</span>.<span style="color: #a6e22e;">stringify</span>({
        destinatario<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
        assunto<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Proposta Completa'</span>,
        corpo_html<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Proposta&lt;/h1&gt;&lt;p&gt;Todos documentos.&lt;/p&gt;'</span>,
        anexos<span style="color: #f92672;">:</span> [
            {<span style="color: #75715e;">// Do frontend (upload)</span>
                base64<span style="color: #f92672;">:</span> base64,
                nome<span style="color: #f92672;">:</span> file.name,
                type<span style="color: #f92672;">:</span> file.type
            },
            {<span style="color: #75715e;">// De URL externa</span>
                url<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://cdn.empresa.com/catalogo.pdf'</span>,
                nome<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'catalogo.pdf'</span>
            },
            {<span style="color: #75715e;">// Outra URL</span>
                url<span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://storage.empresa.com/termos.pdf'</span>
            }
        ]
    })
});

<span style="color: #66d9ef;">const</span> data <span style="color: #f92672;">=</span> <span style="color: #66d9ef;">await</span> response.<span style="color: #a6e22e;">json</span>();
console.<span style="color: #a6e22e;">log</span>(data);</code></pre>
                </div>
            </div>

            <!-- PYTHON TAB -->
            <div class="tab-content" data-lang="python">
                <span class="language-badge python">Python</span>
                <h6 class="text-light mb-3 mt-2"><i class="fas fa-envelope"></i> 1. E-mail Simples</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">requests</span>

<span style="color: #f8f8f2;">url</span> <span style="color: #f92672;">=</span> <span style="color: #e6db74;">'http://api.mailjztech.com/sendEmail'</span>
<span style="color: #f8f8f2;">headers</span> <span style="color: #f92672;">=</span> {
    <span style="color: #e6db74;">'Authorization'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bearer SUA_CHAVE_API'</span>,
    <span style="color: #e6db74;">'Content-Type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/json'</span>
}
<span style="color: #f8f8f2;">data</span> <span style="color: #f92672;">=</span> {
    <span style="color: #e6db74;">'destinatario'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Bem-vindo!'</span>,
    <span style="color: #e6db74;">'corpo_html'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Olá!&lt;/h1&gt;&lt;p&gt;Obrigado por se cadastrar.&lt;/p&gt;'</span>
}

<span style="color: #f8f8f2;">response</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">requests</span>.<span style="color: #a6e22e;">post</span>(<span style="color: #f8f8f2;">url</span>, <span style="color: #f8f8f2;">headers</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">headers</span>, <span style="color: #f8f8f2;">json</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">data</span>)
<span style="color: #f8f8f2;">result</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">response</span>.<span style="color: #a6e22e;">json</span>()

<span style="color: #f92672;">if</span> <span style="color: #f92672;">not</span> <span style="color: #f8f8f2;">result</span>[<span style="color: #e6db74;">'error'</span>]:
    <span style="color: #66d9ef;">print</span>(<span style="color: #e6db74;">'E-mail enviado com sucesso!'</span>)
<span style="color: #f92672;">else</span>:
    <span style="color: #66d9ef;">print</span>(<span style="color: #e6db74;">f'Erro: {result["result"]}'</span>)</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-file-code"></i> 2. Com Anexo Base64</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">requests</span>
<span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">base64</span>

<span style="color: #75715e;"># Ler arquivo e converter para base64</span>
<span style="color: #f92672;">with</span> <span style="color: #66d9ef;">open</span>(<span style="color: #e6db74;">'/path/to/arquivo.pdf'</span>, <span style="color: #e6db74;">'rb'</span>) <span style="color: #f92672;">as</span> <span style="color: #f8f8f2;">f</span>:
    <span style="color: #f8f8f2;">file_content</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">f</span>.<span style="color: #a6e22e;">read</span>()
    <span style="color: #f8f8f2;">base64_content</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">base64</span>.<span style="color: #a6e22e;">b64encode</span>(<span style="color: #f8f8f2;">file_content</span>).<span style="color: #a6e22e;">decode</span>(<span style="color: #e6db74;">'utf-8'</span>)

<span style="color: #f8f8f2;">data</span> <span style="color: #f92672;">=</span> {
    <span style="color: #e6db74;">'destinatario'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Documento Digital'</span>,
    <span style="color: #e6db74;">'corpo_html'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;p&gt;Documento enviado digitalmente.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span><span style="color: #f92672;">:</span> [
        {
            <span style="color: #e6db74;">'base64'</span><span style="color: #f92672;">:</span> <span style="color: #f8f8f2;">base64_content</span>,
            <span style="color: #e6db74;">'nome'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'documento.pdf'</span>,
            <span style="color: #e6db74;">'type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/pdf'</span>
        }
    ]
}

<span style="color: #f8f8f2;">response</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">requests</span>.<span style="color: #a6e22e;">post</span>(<span style="color: #f8f8f2;">url</span>, <span style="color: #f8f8f2;">headers</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">headers</span>, <span style="color: #f8f8f2;">json</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">data</span>)
<span style="color: #66d9ef;">print</span>(<span style="color: #f8f8f2;">response</span>.<span style="color: #a6e22e;">json</span>())</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-link"></i> 3. Com Anexo via URL</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">requests</span>

<span style="color: #f8f8f2;">data</span> <span style="color: #f92672;">=</span> {
    <span style="color: #e6db74;">'destinatario'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Documentos Online'</span>,
    <span style="color: #e6db74;">'corpo_html'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Documentos&lt;/h1&gt;&lt;p&gt;Acesse os anexos.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span><span style="color: #f92672;">:</span> [
        {
            <span style="color: #e6db74;">'url'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://exemplo.com/relatorio.pdf'</span>,
            <span style="color: #e6db74;">'nome'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'relatorio.pdf'</span>,
            <span style="color: #e6db74;">'type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'application/pdf'</span>
        },
        {
            <span style="color: #e6db74;">'url'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://exemplo.com/foto.jpg'</span>
            <span style="color: #75715e;"># nome será extraído da URL automaticamente</span>
        }
    ]
}

<span style="color: #f8f8f2;">response</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">requests</span>.<span style="color: #a6e22e;">post</span>(<span style="color: #f8f8f2;">url</span>, <span style="color: #f8f8f2;">headers</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">headers</span>, <span style="color: #f8f8f2;">json</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">data</span>)
<span style="color: #66d9ef;">print</span>(<span style="color: #f8f8f2;">response</span>.<span style="color: #a6e22e;">json</span>())</code></pre>
                </div>

                <h6 class="text-light mb-3 mt-4"><i class="fas fa-layer-group"></i> 4. Exemplo Completo (3 Tipos Misturados)</h6>
                <div class="code-wrapper">
                    <button class="copy-button"><i class="fas fa-copy"></i> Copiar</button>
                    <pre><code><span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">requests</span>
<span style="color: #f92672;">import</span> <span style="color: #f8f8f2;">base64</span>

<span style="color: #75715e;"># Ler arquivo local e converter</span>
<span style="color: #f92672;">with</span> <span style="color: #66d9ef;">open</span>(<span style="color: #e6db74;">'logo.png'</span>, <span style="color: #e6db74;">'rb'</span>) <span style="color: #f92672;">as</span> <span style="color: #f8f8f2;">f</span>:
    <span style="color: #f8f8f2;">logo_base64</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">base64</span>.<span style="color: #a6e22e;">b64encode</span>(<span style="color: #f8f8f2;">f</span>.<span style="color: #a6e22e;">read</span>()).<span style="color: #a6e22e;">decode</span>(<span style="color: #e6db74;">'utf-8'</span>)

<span style="color: #f8f8f2;">data</span> <span style="color: #f92672;">=</span> {
    <span style="color: #e6db74;">'destinatario'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'cliente@example.com'</span>,
    <span style="color: #e6db74;">'assunto'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'Proposta Comercial Completa'</span>,
    <span style="color: #e6db74;">'corpo_html'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'&lt;h1&gt;Proposta&lt;/h1&gt;&lt;p&gt;Todos documentos.&lt;/p&gt;'</span>,
    <span style="color: #e6db74;">'anexos'</span><span style="color: #f92672;">:</span> [
        {<span style="color: #75715e;"># Imagem em base64</span>
            <span style="color: #e6db74;">'base64'</span><span style="color: #f92672;">:</span> <span style="color: #f8f8f2;">logo_base64</span>,
            <span style="color: #e6db74;">'nome'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'logo.png'</span>,
            <span style="color: #e6db74;">'type'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'image/png'</span>
        },
        {<span style="color: #75715e;"># PDF de URL externa</span>
            <span style="color: #e6db74;">'url'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://cdn.empresa.com/catalogo.pdf'</span>,
            <span style="color: #e6db74;">'nome'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'catalogo.pdf'</span>
        },
        {<span style="color: #75715e;"># Mais URLs</span>
            <span style="color: #e6db74;">'url'</span><span style="color: #f92672;">:</span> <span style="color: #e6db74;">'https://storage.empresa.com/termos.pdf'</span>
        }
    ]
}

<span style="color: #f8f8f2;">response</span> <span style="color: #f92672;">=</span> <span style="color: #f8f8f2;">requests</span>.<span style="color: #a6e22e;">post</span>(<span style="color: #f8f8f2;">url</span>, <span style="color: #f8f8f2;">headers</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">headers</span>, <span style="color: #f8f8f2;">json</span><span style="color: #f92672;">=</span><span style="color: #f8f8f2;">data</span>)
<span style="color: #66d9ef;">print</span>(<span style="color: #f8f8f2;">response</span>.<span style="color: #a6e22e;">json</span>())</code></pre>
                </div>
            </div>
        </div>

        <div class="alert mt-4" style="background: rgba(23, 162, 184, 0.15); border-left: 4px solid #17a2b8; color: #17a2b8; border-radius: 0.5rem;">
            <i class="fas fa-lightbulb"></i> <strong>Dica:</strong>
            Você pode misturar os 3 tipos de anexos no mesmo e-mail! A API detecta automaticamente o tipo baseado nas chaves presentes no objeto.
        </div>
    </div>
</div>