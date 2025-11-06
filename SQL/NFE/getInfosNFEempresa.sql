 select 
   *
 from (
     select
         35                                as coduf
        ,3523909                           as codmunicipio
        ,'JOSE CARLOS ALVES FONSECA'       as nome
        ,'JOSE CARLOS ALVES FONSECA'       as nomefantasia
        ,'387173100115'                    as inscricao_estadual
        ,1                                 as codtrib
        ,'07370593000127'                  as cnpj
        ,'AVENIDA SENADOR TEOTONIO VILELA' as endereco
        ,'141'                             as numero_casa
        ,'JARDIM AEROPORTO I'              as bairro
        ,'ITU'                             as cidade
        ,'SP'                              as uf
        ,'13304550'                        as cep
        ,'11998358363' 					   as celular
        ,3                                 as idempresa
)tb
where tb.idempresa = :idempresa
