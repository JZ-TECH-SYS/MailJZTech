<?php
use core\Router;
$router = new Router();

//-----------------Relatorios----------------//
$router->get('/getVendas/{idempresa}/{dataini}/{datafim}/{idsituacao_pedido_venda}', 'RelatorioController@getVendas', true);
$router->get('/getVendasDiario/{idempresa}/{dataini}/{datafim}', 'RelatorioController@getVendasDiario');
$router->get('/getRelatorioProdutos/{idempresa}/{dataini}/{datafim}', 'RelatorioController@getRelatorioProdutos', true);
$router->get('/relatorioPedidosNotas/{idempresa}/{dataini}/{datafim}', 'RelatorioController@relatorioPedidosNotas', true);
$router->get('/getDash/{idempresa}', 'RelatorioController@getDash', true);

// Rota principal
$router->get('/', 'HomeController@index');

//-----------------EMAIL API (MailJZTech)----------------//
// Envio de e-mails
$router->post('/sendEmail', 'EmailController@sendEmail');
$router->get('/listarEmails', 'EmailController@listarEmails');
$router->get('/detalheEmail', 'EmailController@detalheEmail');
$router->get('/statsEmails', 'EmailController@statsEmails');
$router->post('/testarEmail', 'EmailController@testarEmail');
$router->get('/validarConfigEmail', 'EmailController@validarConfigEmail');

// Gerenciamento de sistemas
$router->get('/listarSistemas', 'SistemasController@listarSistemas', true);
$router->get('/obterSistema', 'SistemasController@obterSistema', true);
$router->post('/criarSistema', 'SistemasController@criarSistema', true);
$router->put('/atualizarSistema', 'SistemasController@atualizarSistema', true);
$router->delete('/deletarSistema', 'SistemasController@deletarSistema', true);
$router->post('/regenerarChaveApi', 'SistemasController@regenerarChaveApi', true);

//-----------------LOGIN----------------//
$router->get('/sair', 'LoginController@logout', true);
$router->get('/validaToken', 'LoginController@validaToken');
$router->post('/login', 'LoginController@verificarLogin');

//-----------------Bairros----------------//
$router->get('/getBairros/{idempresa}', 'BairrosController@getBairros');
$router->get('/getBairroById/{idbairro}/{idempresa}', 'BairrosController@getBairroById', true);
$router->post('/addBairro', 'BairrosController@addBairro', true);
$router->put('/editBairro', 'BairrosController@editBairro', true);
$router->delete('/deleteBairro', 'BairrosController@deleteBairro', true);

//-----------------Geolocalização Delivery----------------//
$router->post('/geocodificarEndereco', 'LocalizacaoController@geocodificarEndereco');
$router->post('/validarGeolocalizacao', 'LocalizacaoController@validarGeolocalizacao');
$router->post('/salvarLocalizacaoCliente', 'LocalizacaoController@salvarLocalizacaoCliente');
$router->get('/gerarQRCodeEntrega/{idpedidovenda}', 'LocalizacaoController@gerarQRCodeEntrega', true);

//-----------------Auto-CEP----------------//
$router->get('/auto-cep/status/{idempresa}', 'AutoCepController@checkAutoCepStatus');
$router->post('/auto-cep/resolver-endereco', 'AutoCepController@resolverEndereco');
$router->post('/auto-cep/consultar-externo', 'AutoCepController@consultarCepExterno', true);
$router->post('/auto-cep/testar-google-maps', 'AutoCepController@testarTodasApis', true); // GOOGLE MAPS APENAS
$router->get('/auto-cep/estatisticas/{idempresa}', 'AutoCepController@estatisticasCache', true); // NOVO
$router->delete('/auto-cep/limpar-cache/{idempresa}', 'AutoCepController@limparCache', true);
$router->get('/cep/{idempresa}', 'CepController@getCepData'); // NOVO - Auto-preenchimento completo

//-----------------Categorias----------------//
$router->get('/getCategorias/{idempresa}', 'CategoriaController@getCategorias', true);
$router->get('/getCategoriaById/{idempresa}/{idcategoria}', 'CategoriaController@getCategoriaById', true);
$router->post('/addCategoria', 'CategoriaController@addCategoria', true);
$router->put('/editCategoria', 'CategoriaController@editCategoria', true);
$router->delete('/deleteCategoria', 'CategoriaController@deleteCategoria', true);

//---------------Produtos----------------//
$router->get('/getProdutos/{idempresa}/{tipo}', 'ProdutosController@getProdutos', true);
$router->get('/{idempresa}/getBalance', 'ProdutosController@getProdutosBalance');
$router->get('/getProdutosAcrescimos/{idempresa}/{tipo}', 'ProdutosController@getProdutosAcrescimos', true);
$router->get('/getProdutosById/{idempresa}/{idproduto}', 'ProdutosController@getProdutosById', true);
$router->post('/addProduto', 'ProdutosController@addProduto', true);
$router->post('/editProduto', 'ProdutosController@editProduto', true);
$router->delete('/deleteProduto', 'ProdutosController@deleteProduto', true);

//---------------Pessoa----------------//
$router->get('/getPessoas/{idempresa}', 'PessoaController@getPessoas', true);
$router->get('/getPessoaById/{idempresa}/{idpessoa}', 'PessoaController@getPessoaById', true);
$router->post('/addPessoa', 'PessoaController@addPessoa', true);
$router->put('/editPessoa', 'PessoaController@editPessoa', true);
$router->delete('/deletePessoa', 'PessoaController@deletePessoa', true);

//---------------Usuarios----------------//
$router->get('/getUsuarios/{idempresa}', 'UsuariosController@getUsuarios', true);
$router->get('/getUsuarios/{idempresa}/{nome}', 'UsuariosController@getUsuarios', true);
$router->put('/editUsuario', 'UsuariosController@editUsuario', true);

//---------------Menus----------------//
$router->get('/getMenus/{idempresa}', 'MenuController@getMenus', true);
$router->get('/getMenuById/{idempresa}/{idmenu}', 'MenuController@getMenuById', true);
$router->get('/getMenuOrders', 'MenuController@getMenuOrders', true);
$router->post('/addMenu', 'MenuController@addMenu', true);
$router->put('/editMenu', 'MenuController@editMenu', true);
$router->delete('/deleteMenu', 'MenuController@deleteMenu', true);
$router->put('/menus/ordenacao', 'MenuController@ordenarMenus', true);
$router->put('/menus/{idmenu}/produtos/ordenacao', 'MenuController@ordenarProdutosMenu', true);
$router->put('/menus/{idmenu}/produtos/swap', 'MenuController@swapProdutoMenuOrdem', true);

//---------------ProdutosMenu----------------//

//---------------Pagamentos----------------//
$router->get('/getPagamentos/{idempresa}/{idpedidovenda}', 'PagamentoController@getPagamentos', true);
$router->get('/getPagamentosById/{idempresa}/{idpedidovenda}/{idpagamento}', 'PagamentoController@getPagamentosById', true);
$router->post('/addPagamento', 'PagamentoController@addPagamento', true);
$router->put('/editPagamento', 'PagamentoController@editPagamento', true);
$router->delete('/deletePagamento', 'PagamentoController@deletePagamento', true);
$router->delete('/deleteAllPagamentos', 'PagamentoController@deleteAllPagamentos', true);
$router->post('/updateCaut', 'PagamentoController@updateCAut', true);

//---------------ProdutosMenu----------------//
$router->get('/getProdutoMenu/{idempresa}', 'MenuController@getProdutosMenu', true);
$router->get('/getProdutoMenuOn/{idempresa}', 'MenuController@getProdutosMenuOn');
$router->post('/addProdutoMenu', 'MenuController@addProdutoMenu', true);
$router->delete('/deleteProdutoMenu', 'MenuController@deleteProdutoMenu', true);

//---------------Limita-Acrescimos-Produtos----------------//
$router->get('/getLimitProdutos/{idempresa}', 'LimitarAcrescimoController@getLimitProdutos');
$router->post('/addLimitProduto', 'LimitarAcrescimoController@addLimitProduto', true);
$router->delete('/deleteLimitProduto', 'LimitarAcrescimoController@deleteLimitProduto', true);

//---------------Trava-Acrescimos-Produtos----------------//
$router->get('/getTravaProdutos/{idempresa}', 'TravaAcrescimoController@getTravaProdutos');
$router->post('/addTravaProduto', 'TravaAcrescimoController@addTravaProduto', true);
$router->delete('/deleteTravaProduto', 'TravaAcrescimoController@deleteTravaProduto', true);

//---------------Próxima Mesa----------------//
$router->get('/getProximaMesa/{idempresa}', 'ProximaMesaController@getProximaMesa', true);

//---------------Acesso Comanda via Link----------------//
$router->get('/validarAcessoComanda/{idempresa}/{mesa}/{token}', 'ComandaAcessoController@validarAcesso');
$router->post('/addItemComanda', 'ComandaAcessoController@addItemComanda');

//---------------comandas-pedido-venda----------------//
$router->get('/getPedidos/id/{idempresa}/{idpedidovenda}', 'PedidoVendaController@getPedidosById', true);
$router->get('/getPedidos/{idempresa}/{idsituacao}', 'PedidoVendaController@getPedidos', true);
$router->get('/getPedidosOnline/{idempresa}/{idsituacao}/{origin}', 'PedidoVendaController@getPedidosOnline', true);
$router->get('/getLastOrdersPhone/{idempresa}/{celular}', 'PedidoVendaController@getLastOrdersPhone');
$router->post('/addPedido', 'PedidoVendaController@addPedido', true);
$router->put('/editPedido', 'PedidoVendaController@editPedido', true);
$router->delete('/deletePedido', 'PedidoVendaController@deletePedido', true);

//-->comandas->Itens
$router->post('/addPedidoItem', 'PedidoVendaController@addPedidoItem', true);
$router->put('/editPedidoItem', 'PedidoVendaController@editPedidoItem', true);
$router->delete('/deletePedidoItem', 'PedidoVendaController@deletePedidoItem', true);

//-->comandas->Itens->acrescimos
$router->post('/addPedidoItemAcrescimo', 'PedidoVendaController@addPedidoItemAcrescimo', true);
$router->put('/editPedidoItemAcrescimo', 'PedidoVendaController@editPedidoItemAcrescimo', true);
$router->delete('/deletePedidoItemAcrescimo', 'PedidoVendaController@deletePedidoItemAcrescimo', true);

//---------------PedidoVenda IA----------------//
$router->post('/api/pedido-venda-ia/{idempresa}', 'IAPedidoVendaController@acao', true);


//------------------Utils----------------//
$router->put('/alterarQuantidade', 'PedidoVendaController@alterarQuantidade', true);
$router->get('/getMeiosPagamentos/{idempresa}', 'PagamentoController@getMeiosPagamentos');
$router->get('/getMeiosPagamentosOn/{idempresa}', 'PagamentoController@getMeiosPagamentosOn');
$router->get('/getTiposProdutos/{idempresa}', 'ProdutosController@getTiposProdutos', true);
$router->get('/getCidades/{filtro}', 'CidadeCrontoller@getCidades', true);
$router->get('/getCidadeById/{idcidade}', 'CidadeCrontoller@getCidadeById', true);
$router->get('/getCity/id/{idempresa}', 'CidadeCrontoller@getCity', true);
$router->get('/gerarBKP/{enviar}/{token}', 'BKPController@gerar');

//---------------Despesas----------------//
$router->get('/getDespesas/{idempresa}', 'DespesasController@getDespesas', true);
$router->get('/getDespesasById/{idempresa}/{iddespesa}', 'DespesasController@getDespesasById', true);
$router->get('/getDespesasPorPeriodo/{idempresa}/{dataini}/{datafim}', 'DespesasController@getDespesasPorPeriodo', true);
$router->get('/getDespesasPorCategoria/{idempresa}/{dataini}/{datafim}', 'DespesasController@getDespesasPorCategoria', true);
$router->post('/addDespesa', 'DespesasController@addDespesa', true);
$router->put('/editDespesa', 'DespesasController@editDespesa', true);
$router->delete('/deleteDespesa', 'DespesasController@deleteDespesa', true);

//---------------Tipo de Despesa----------------//
$router->get('/getTiposDespesa/{idempresa}', 'TipoDespesaController@getTiposDespesa', true);
$router->get('/getTipoDespesa/{idtipo_despesa}/{idempresa}', 'TipoDespesaController@getTipoDespesa', true);
$router->post('/addTipoDespesa', 'TipoDespesaController@addTipoDespesa', true);
$router->put('/editTipoDespesa', 'TipoDespesaController@editTipoDespesa', true);
$router->delete('/deleteTipoDespesa', 'TipoDespesaController@deleteTipoDespesa', true);

//---------------Relatórios Financeiros----------------//
$router->get('/getRelatorioFinanceiro/{idempresa}/{dataini}/{datafim}', 'RelatorioFinanceiroController@getRelatorioFinanceiro', true);
$router->get('/getRelatorioFinanceiroDia/{idempresa}/{dia}', 'RelatorioFinanceiroController@getRelatorioFinanceiroDia', true);
$router->get('/getRelatorioVendaCusto/{idempresa}/{dataini}/{datafim}', 'RelatorioVendaCustoController@getRelatorioVendaCusto', true);
$router->get('/getRelatorioVendaCustoDia/{idempresa}/{dia}', 'RelatorioVendaCustoController@getRelatorioVendaCustoDia', true);

//---------------PRINT----------------//
//---------------PRINT----------------//
$router->get('/getPrint/{idempresa}/{idpedidovenda}/{idpedidovendaitem}', 'PrinterController@getPrint', true);
$router->get('/sendPrint/{idempresa}/{idpedidovenda}/{idpedidovendaitem}', 'PrinterController@sendPrint');
$router->get('/cronImpressaoDireta', 'PrinterController@cronImpressaoDireta', true);
$router->get('/cronImpressaoDiretav2/{idempresa}', 'PrinterController@cronImpressaoDiretav2');
$router->get('/cronImpressaoDiretav3/{idempresa}', 'PrinterController@cronImpressaoDiretav3'); // ✅ NOVA ROTA COM IMPRESSORA POR CATEGORIA
$router->get('/ImpressaoDireta/{idempresa}/lista', 'PrinterController@lista', true);
$router->get('/ImpressaoDireta/remove/{idempresa}/{idprint}', 'PrinterController@remove', true);
$router->get('/ImpressaoDireta/{idempresa}', 'PrinterController@cronImpressaoDireta', true);

//---------------Qrcode----------------//
$router->get('/gerarQRSalao/{idempresa}', 'QrcodeController@gerarQRSalao', true);
$router->get('/gerarQRMesa/{idempresa}/{idmesa}', 'QrcodeController@gerarQRMesa', true);

//------PEDIDOS-ONLINE-infos------//
$router->get('/getInfosCliente/{idempresa}/{numero}', 'ParametrizacaoController@getInfosCliente');
$router->get('/getInfosPedidoOn/{nome_empresa}', 'ParametrizacaoController@getInfoPsedidoOn');
$router->post('/editInfosPedidoOn', 'ParametrizacaoController@editInfosPedidoOn');
$router->get('/getInfoSiteParams/{idempresa}', 'ParametrizacaoController@getInfoSiteParams');
$router->post('/addPedidoCompleto', 'PedidoVendaController@addPedidoCompleto');

$router->get('/abre-fecha-site/{idempresa}','ParametrizacaoController@abreFechaSite',true);
$router->get('/site-status/{idempresa}', 'ParametrizacaoController@getSiteStatus');


//-----------------Cupon-----------------//
$router->get('/getCupons/{idempresa}', 'CuponController@getCupons',true);
$router->get('/getCuponById/{idcupon}/{idempresa}', 'CuponController@getCuponById', true);
$router->post('/addCupon', 'CuponController@addCupon', true);
$router->put('/editCupon', 'CuponController@editCupon', true);
$router->delete('/deleteCupon', 'CuponController@deleteCupon', true);


//-----------------CuponRegra-----------------//
$router->get('/getCuponRegras', 'CuponRegraController@getCuponRegras',true);
$router->get('/getCuponRegraById/{idcuponregra}', 'CuponRegraController@getCuponRegraById', true);
$router->post('/addCuponRegra', 'CuponRegraController@addCuponRegra', true);
$router->put('/editCuponRegra', 'CuponRegraController@editCuponRegra', true);
$router->delete('/deleteCuponRegra', 'CuponRegraController@deleteCuponRegra', true);


//-----------------CuponPedidos-----------------//
$router->get('/getCuponPedidos/{idempresa}', 'CuponPedidosController@getCuponPedidos',true);
$router->get('/getCuponPedidoById/{idpedidovenda}/{idcupon}/{idempresa}', 'CuponPedidosController@getCuponPedidoById', true);
$router->post('/addCuponPedido', 'CuponPedidosController@addCuponPedido', true);
$router->put('/editCuponPedido', 'CuponPedidosController@editCuponPedido', true);
$router->delete('/deleteCuponPedido', 'CuponPedidosController@deleteCuponPedido', true);

//-----------------Selo Extra-----------------//
$router->get('/api/selos', 'SeloExtraController@index', true);
$router->post('/api/selos', 'SeloExtraController@store', true);
$router->put('/api/selos/{id}', 'SeloExtraController@update', true);
$router->delete('/api/selos/{id}', 'SeloExtraController@destroy', true);
$router->get('/api/selos/analitico', 'SeloExtraController@clientes', true);
$router->get('/api/selos/clientes', 'SeloExtraController@clientes', true);

//---------------cron----------------//
$router->get('/cron-cupon/{token}', 'ParametrizacaoController@validarCupon');

//-----------------parametrizacao-----------------//
$router->get('/parametrizacao-myzap/status/{idempresa}', 'ParametrizacaoController@getMyzapStatus',true);
$router->get('/parametrizacao-myzap/qrcode/{idempresa}', 'ParametrizacaoController@getMyzapQRCode',true);
$router->get('/parametrizacao-myzap/logout/{idempresa}', 'ParametrizacaoController@disconnectMyzap',true);
$router->get('/parametrizacao-myzap/send-nfe-pdf/{idempresa}/{idpedidovenda}', 'ParametrizacaoController@sendNfePdf',true);
$router->post('/parametrizacao-myzap/send-order/{idempresa}/{idpedidovenda}', 'ParametrizacaoController@sendOrderWhatsapp',true);
$router->post('/parametrizacao-myzap/send-test/{idempresa}', 'ParametrizacaoController@sendTestMessage',true);
$router->post('/parametrizacao-myzap/update-config/{idempresa}', 'ParametrizacaoController@updateMyzapConfig',true);
$router->get('/parametrizacao-myzap/config/{idempresa}', 'ParametrizacaoController@getMyzapConfig',true);

//---------------Controler Fiscal----------------//
$router->get('/regrasFiscais/{idempresa}', 'ControleFiscalController@regrasFiscais', true);
$router->get('/getRegraFiscalById/{idempresa}/{idregra_fiscal}', 'ControleFiscalController@getRegraFiscalById', true);
$router->post('/addRegraFiscal', 'ControleFiscalController@addRegraFiscal', true);
$router->put('/editRegraFiscal', 'ControleFiscalController@editRegraFiscal', true);
$router->delete('/deleteRegraFiscal', 'ControleFiscalController@deleteRegraFiscal', true);

//---------------NFE----------------//
$router->get('/gerarNotaPedido/{idempresa}/{idpedidovenda}/{tiponota}', 'NotaFiscalController@gerarNota', true);
$router->get('/processarNotas', 'NotaFiscalController@processarNotasPendente', true);
$router->get('/cancelarNota/{idempresa}/{idregistronota}', 'NotaFiscalController@cancelarNota', true);
$router->get('/reenviarNota/{idempresa}/{idregistronota}', 'NotaFiscalController@reenviarNota', true);
$router->get('/inutilizarNota/{idempresa}', 'NotaFiscalController@inutilizarNota', true);


$router->get('/impressao/{idempresa}/{idpedidovenda}', 'NotaFiscalController@imprimir', true);
$router->get('/listaNotas/{idempresa}/{dataini}/{datafim}', 'NotaFiscalController@listaNotas', true);


$router->get('/cfops/listar', 'NotaFiscalController@getCfops', true);
$router->get('/ncm-cest/listar', 'NotaFiscalController@getNcmCest', true);
$router->get('/tipo-imposto/listar', 'NotaFiscalController@getTipoImposto', true);
$router->get('/origin-mercadoria/listar', 'NotaFiscalController@getOrigemMercadoria', true);
$router->get('/ufs/listar', 'NotaFiscalController@getUfs', true);


//---------------Email----------------//
$router->get('/enviar-notas-contabilidade', 'EmailContabilidadeController@enviarPendentes');
$router->get('/testar-email', 'EmailController@testEmail');
$router->get('/validar-email-config', 'EmailController@validateEmailConfig');

//---------------Address Services----------------//
$router->get('/address/cep/{cep}', 'AddressController@getAddressByCep');
$router->get('/address/search/{uf}/{cidade}/{logradouro}', 'AddressController@getCepByAddress');
$router->get('/address/coordinates', 'AddressController@getCoordinatesByAddress');
$router->get('/address/distance', 'AddressController@calculateDistance');
$router->get('/address/providers', 'AddressController@getAvailableProviders');

//---------------Mesa Salão----------------//
$router->get('/getMesasSalao/{idempresa}', 'MesaSalaoController@getMesasSalao', true);
$router->get('/getMesasSalaoAtivas/{idempresa}', 'MesaSalaoController@getMesasSalaoAtivas', true);
$router->post('/addMesaSalao', 'MesaSalaoController@addMesaSalao', true);
$router->put('/editMesaSalao', 'MesaSalaoController@editMesaSalao', true);
$router->delete('/deleteMesaSalao/{idmesa}/{idempresa}', 'MesaSalaoController@deleteMesaSalao', true);

//---------------Mesa Reserva----------------//
$router->get('/getMesasReservas/{idempresa}/{status}', 'MesaReservaController@getMesasReservas', true);
$router->get('/getReservaAtivaByMesa/{idempresa}/{idmesa}', 'MesaReservaController@getReservaAtivaByMesa'); // Rota pública
$router->post('/addMesaReserva', 'MesaReservaController@addMesaReserva', true);
$router->put('/editMesaReserva', 'MesaReservaController@editMesaReserva', true);
$router->put('/cancelarMesaReserva/{idreserva}/{idempresa}', 'MesaReservaController@cancelarMesaReserva', true);
$router->delete('/deleteMesaReserva/{idreserva}/{idempresa}', 'MesaReservaController@deleteMesaReserva', true);
