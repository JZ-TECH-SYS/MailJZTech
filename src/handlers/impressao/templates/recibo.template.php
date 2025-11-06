{{css}}<div class="wrap">
{{empresa_nome}}
{{empresa_endereco}}
{{#if empresa_cnpj}}CNPJ: {{empresa_cnpj}}{{/if}}
==============================
{{empresa_dilema}}Nº Pedido: {{numero_pedido}}
Data: {{data_pedido}}
{{#if qtd_pedidos}}{{qtd_pedidos}}{{/if}}
Cliente: {{cliente_nome}}
{{#if celular}}{{celular}}{{/if}}
{{#if mesa}}{{mesa}}{{/if}}
{{cupons_disponiveis}}
{{secao_entrega}}
{{#if campos_adionais}}
=== CAMPOS ADICIONAIS ===
{{campos_adionais}}
{{/if}}
{{itens}}
========= PAGAMENTOS ==========
{{#if taxa}}{{taxa}}{{/if}}
{{#if cupons_desconto}}{{cupons_desconto}}{{/if}}
{{total}}
{{pagamento}}
{{#if troco}}{{troco}}{{/if}}

Agradecemos pela sua 
preferência!
Esperamos vê-lo(a) novamente 
em breve!
{{#if geo_qr}}
==============================
Leia o QR Code 
para ir até o Maps{{/if}}
{{#if geo_qr}}<img src="{{geo_qr}}" style="display:block;margin:0;padding:0;" />{{/if}}
{{#if geo_coords}}{{geo_coords}}{{/if}}
</div>