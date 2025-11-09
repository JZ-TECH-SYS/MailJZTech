<div class="card mb-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem;">
    <div class="card-header text-white" style="background: linear-gradient(135deg, #fbc2eb 0%, #a6c1ee 100%); border-radius: 1rem 1rem 0 0;">
        <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Códigos de Erro</h4>
    </div>
    <div class="card-body">
        <p class="text-light mb-4">
            A API retorna códigos HTTP padrão e mensagens de erro descritivas para facilitar a depuração.
        </p>

        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead style="background: rgba(255,255,255,0.1);">
                    <tr>
                        <th>Código HTTP</th>
                        <th>Significado</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody style="background: rgba(0,0,0,0.3);">
                    <tr>
                        <td><span class="badge bg-success" style="font-size: 0.95rem;">200</span></td>
                        <td class="text-success">OK</td>
                        <td class="text-light-50">Requisição bem-sucedida</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning text-dark" style="font-size: 0.95rem;">400</span></td>
                        <td class="text-warning">Bad Request</td>
                        <td class="text-light-50">Parâmetros inválidos ou ausentes</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger" style="font-size: 0.95rem;">401</span></td>
                        <td class="text-danger">Unauthorized</td>
                        <td class="text-light-50">Token de autenticação inválido ou ausente</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger" style="font-size: 0.95rem;">403</span></td>
                        <td class="text-danger">Forbidden</td>
                        <td class="text-light-50">Sem permissão para acessar o recurso</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-info" style="font-size: 0.95rem;">404</span></td>
                        <td class="text-info">Not Found</td>
                        <td class="text-light-50">Recurso não encontrado</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger" style="font-size: 0.95rem;">500</span></td>
                        <td class="text-danger">Internal Server Error</td>
                        <td class="text-light-50">Erro interno do servidor</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h5 class="text-light mb-3 mt-4"><i class="fas fa-file-code text-warning"></i> Formato de Resposta de Erro</h5>
        <div style="background: #1e1e1e; border: 1px solid #404040; border-radius: 0.375rem; padding: 1rem; overflow-x: auto;">
            <pre style="margin: 0;"><code style="color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 0.9rem;">{
  <span style="color: #9cdcfe;">"result"</span>: <span style="color: #ce9178;">"Mensagem de erro descritiva"</span>,
  <span style="color: #9cdcfe;">"error"</span>: <span style="color: #569cd6;">true</span>
}</code></pre>
        </div>

        <h5 class="text-light mb-3 mt-4"><i class="fas fa-list-ul text-primary"></i> Erros Comuns</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3" style="background: rgba(220, 53, 69, 0.15); border: 1px solid #dc3545; border-radius: 0.5rem;">
                    <h6 class="text-danger mb-2"><i class="fas fa-times-circle"></i> Campos obrigatórios ausentes</h6>
                    <p class="text-light-50 mb-0" style="font-size: 0.9rem;">
                        Verifique se todos os parâmetros obrigatórios foram enviados: <code style="background: rgba(0,0,0,0.3); color: #f92672;">destinatario</code>, <code style="background: rgba(0,0,0,0.3); color: #f92672;">assunto</code>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background: rgba(220, 53, 69, 0.15); border: 1px solid #dc3545; border-radius: 0.5rem;">
                    <h6 class="text-danger mb-2"><i class="fas fa-key"></i> Token inválido</h6>
                    <p class="text-light-50 mb-0" style="font-size: 0.9rem;">
                        Verifique se o header <code style="background: rgba(0,0,0,0.3); color: #87ceeb;">Authorization: Bearer TOKEN</code> está correto
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background: rgba(220, 53, 69, 0.15); border: 1px solid #dc3545; border-radius: 0.5rem;">
                    <h6 class="text-danger mb-2"><i class="fas fa-envelope"></i> E-mail inválido</h6>
                    <p class="text-light-50 mb-0" style="font-size: 0.9rem;">
                        O endereço de e-mail deve estar em formato válido (exemplo@dominio.com)
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background: rgba(220, 53, 69, 0.15); border: 1px solid #dc3545; border-radius: 0.5rem;">
                    <h6 class="text-danger mb-2"><i class="fas fa-paperclip"></i> Anexo não encontrado</h6>
                    <p class="text-light-50 mb-0" style="font-size: 0.9rem;">
                        Verifique se o caminho do arquivo existe ou se o base64/URL está válido
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>