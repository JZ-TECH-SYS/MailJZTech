select 
  *
from (
    select
        p.nome
       ,9 as tipo_ie
       ,'INSENTO' as ie
       ,p.cpf
       ,'Sem Email' as email
       ,c.nome as cidade
       ,es.uf
       ,coalesce(p.celular,'Sem Telefone') as celular 
       ,1 as ordem
    from pessoa p 
    left join endereco en  on en.idcliente = p.idcliente and en.idempresa = p.idempresa
    left join cidade c     on c.id  = en.idcidade
    left join estado es    on es.id = c.uf
    where p.idempresa  = :idempresa
      and p.idcliente  = :idpessoa
    
    union all

      select
        p.nome
       ,9 as tipo_ie
       ,'INSENTO' as ie
       ,p.cpf
       ,'Sem Email' as email
       ,c.nome as cidade
       ,es.uf
       ,coalesce(p.celular,'Sem Telefone') as celular 
       ,2 as ordem
    from pessoa p 
    left join endereco en  on en.idcliente = p.idcliente and en.idempresa = p.idempresa
    left join cidade c     on c.id  = en.idcidade
    left join estado es    on es.id = c.uf
    where p.idempresa  = :idempresa
      and p.nome = 'CONSUMIDOR NÃO IDENTIFICADO'
)tb  
where not cpf is null
  and char_length(cpf) >= 11
order by ordem 
limit 1