-- 🧼 Limpa variáveis se estiver rodando múltiplos inserts
SET @IDEMPRESA := 0;

-- 💼 1. Inserção da nova empresa
INSERT INTO
    empresa (
        nome,
        nomefantasia,
        cnpj,
        endereco,
        numero,
        dilema,
        idcidade,
        chavepix,
        cell
    )
VALUES (
        'Nome da Empresa Nova',
        'Nome Fantasia',
        12345678901234,
        'Rua Exemplo',
        123,
        'Slogan da empresa',
        '9999', -- ID cidade
        'chavepix@pix.com',
        '(44) 99999-9999'
    );

-- 🧠 2. Captura o ID da nova empresa cadastrada
SET @IDEMPRESA := LAST_INSERT_ID();

-- ⚙️ 3. Clona os parâmetros da empresa base (empresa ID = 1)
INSERT INTO
    empresa_parametro (idparametro, idempresa)
SELECT idparametro, @IDEMPRESA
FROM empresa_parametro
WHERE
    idempresa = 1;

-- 💳 4. Clona os tipos de pagamento da empresa base (empresa ID = 1)
INSERT INTO
    tipo_pagamento (
        descricao,
        forma_recebimento,
        idempresa,
        campo_extra1,
        campo_extra2 -- adicione os campos reais aqui
    )
SELECT
    descricao,
    forma_recebimento,
    @IDEMPRESA,
    campo_extra1,
    campo_extra2
FROM tipo_pagamento
WHERE
    idempresa = 1;

-- 👤 5. Cria um usuário administrador padrão
-- Use a função PHP password_hash('senha123', PASSWORD_DEFAULT)
INSERT INTO
    usuario (
        nome,
        usuario,
        senha,
        idempresa,
        admin,
        atendente,
        status
    )
VALUES (
        'Administrador',
        'admin',
        'SENHA_HASH_PHP_AQUI',
        @IDEMPRESA,
        1, -- admin
    );

INSERT INTO
    usuario (
        nome,
        usuario,
        senha,
        idempresa,
        admin,
        atendente,
        status
    )
VALUES (
        'Administrador',
        'admin',
        'SENHA_HASH_PHP_AQUI',
        @IDEMPRESA,
        1, --padrao
    );

INSERT INTO
    `empresa_horarios` (
        `idhorario`,
        `idempresa`,
        `aberto`,
        `dia_semana`,
        `hora_abertura`,
        `hora_fechamento`
    )
VALUES (
        NULL,
        '14',
        '1',
        '3',
        '17:00:00',
        '00:30:00'
    ),
    (
        NULL,
        '14',
        '1',
        '0',
        '00:00:00',
        '03:00:00'
    ),
    (
        NULL,
        '14',
        '1',
        '6',
        '07:00:00',
        '03:00:00'
    ),
    (
        NULL,
        '14',
        '1',
        '5',
        '17:00:00',
        '03:00:00'
    ),
    (
        NULL,
        '14',
        '1',
        '4',
        '17:00:00',
        '00:30:00'
    ),
    (
        NULL,
        '14',
        '1',
        '2',
        '17:00:00',
        '00:30:00'
    ),
    (
        NULL,
        '14',
        '1',
        '1',
        '17:00:00',
        '00:30:00'
    )