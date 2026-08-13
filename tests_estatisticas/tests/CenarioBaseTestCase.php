<?php

use PHPUnit\Extensions\Selenium2TestCase;

/**
 * Classe base contendo rotinas comuns utilizadas nos casos de teste do módulo de Resposta.
 */
class CenarioBaseTestCase extends Selenium2TestCase
{
    protected $paginaBase = null;
    protected $paginaControleProcesso = null;
    protected $paginaAgendamentos = null;

    public function setUpPage(): void
    {
        $this->paginaBase = new PaginaTeste($this);
        $this->paginaControleProcesso = new PaginaControleProcesso($this);
        $this->paginaAgendamentos = new PaginaAgendamentos($this);
        $this->currentWindow()->maximize();
    }

    public static function setUpBeforeClass(): void
    {

    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setHost(PHPUNIT_HOST);
        $this->setPort(intval(PHPUNIT_PORT));
        $this->setBrowser(PHPUNIT_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTS_URL);
        $chromeArgs = [
        '--no-sandbox',
        // O SEI monta os links absolutos a partir de HOST_URL, que aponta
        // para localhost:8000 -- endereco valido para o navegador do
        // desenvolvedor, mas nao para o Chrome dentro do container, onde
        // nada escuta nessa porta. Sem este mapeamento toda navegacao morre
        // em ERR_CONNECTION_REFUSED. Vale so' para o navegador de teste.
        '--host-resolver-rules=MAP localhost org-http',
        '--profile-directory=' . uniqid(),
        '--disable-features=TranslateUI',
        '--disable-translate',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--window-size=1920,1080',
        ];

        if (getenv('GITHUB_ACTIONS') === 'true') {
            $chromeArgs[] = '--headless';
        }
        $this->setDesiredCapabilities(
            array(
                'platform' => 'LINUX',
                'chromeOptions' => array(
                    'w3c' => false,
                    'args' => $chromeArgs,
                )
            )
        );
    }

    protected function definirContextoTeste($nomeContexto)
    {
        return array(
            'URL' => constant($nomeContexto . '_URL'),
            'ORGAO' => constant($nomeContexto . '_SIGLA_ORGAO'),
            'SIGLA_UNIDADE' => constant($nomeContexto . '_SIGLA_UNIDADE'),
            'LOGIN' => constant($nomeContexto . '_USUARIO_LOGIN'),
            'SENHA' => constant($nomeContexto . '_USUARIO_SENHA'),
            'TIPO_DOCUMENTO' => constant($nomeContexto . '_TIPO_DOCUMENTO'),
            'CARGO_ASSINATURA' => constant($nomeContexto . '_CARGO_ASSINATURA'),
        );
    }

    protected function acessarSistema($url, $siglaUnidade, $login, $senha)
    {
        $this->url($url);
        PaginaLogin::executarAutenticacao($this, $login, $senha);
        PaginaTeste::selecionarUnidadeContexto($this, $siglaUnidade);
        $this->url($url);
    }

    /**
     * Abre um processo a partir do seu protocolo, navegando pelo Controle de Processos.
     */
    protected function abrirProcesso($protocolo)
    {
        $this->paginaBase->navegarParaControleProcesso();
        $this->paginaControleProcesso->abrirProcesso($protocolo);
    }

    /**
     * Assina o documento atualmente aberto, reutilizando a PaginaAssinaturaDocumento.
     */
    protected function assinarDocumento($cargoAssinante, $loginSenha)
    {
        $this->paginaDocumento->navegarParaAssinarDocumento();
        sleep(2);

        $this->paginaAssinaturaDocumento->selecionarCargoAssinante($cargoAssinante);
        $this->paginaAssinaturaDocumento->assinarComLoginSenha($loginSenha);
        sleep(5);
    }

    /**
     * Gera um processo no SEI por meio da chamada SOAP "gerarProcedimento" do SeiWS,
     * retornando o protocolo formatado do processo criado.
     *
     * Centraliza a etapa de preparacao (mock SOAP) comum aos cenarios de Enviar Resposta.
     */
    protected function gerarProcessoViaWebService(
        $contexto,
        $idUnidade = '110000001',
        $idTipoProcedimento = '100000381',
        $siglaSistema = 'PD_GOV_BR',
        $identificacaoServico = 'SeiResposta'
    ) {
        $url = $contexto['URL'] . '/ws/SeiWS.php';

        $payload = <<<XML
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sei="Sei" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
    <soapenv:Header/>
    <soapenv:Body>
        <sei:gerarProcedimento soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
            <SiglaSistema xsi:type="xsd:string">{$siglaSistema}</SiglaSistema>
            <IdentificacaoServico xsi:type="xsd:string">{$identificacaoServico}</IdentificacaoServico>
            <IdUnidade xsi:type="xsd:string">{$idUnidade}</IdUnidade>
            <Procedimento xsi:type="sei:Procedimento">
                <IdTipoProcedimento xsi:type="xsd:string">{$idTipoProcedimento}</IdTipoProcedimento>
                <NumeroProtocolo xsi:type="xsd:string"></NumeroProtocolo>
                <DataAutuacao xsi:type="xsd:string"></DataAutuacao>
                <Especificacao xsi:type="xsd:string"></Especificacao>
                <Assuntos xsi:type="sei:ArrayOfAssunto" SOAP-ENC:arrayType="sei:Assunto[]"/>
                <Interessados xsi:type="sei:ArrayOfInteressado" SOAP-ENC:arrayType="sei:Interessado[]"/>
                <Observacao xsi:type="xsd:string"></Observacao>
                <NivelAcesso xsi:type="xsd:string">0</NivelAcesso>
                <IdHipoteseLegal xsi:type="xsd:string"></IdHipoteseLegal>
            </Procedimento>
            <Documentos xsi:type="sei:ArrayOfDocumento" SOAP-ENC:arrayType="sei:Documento[]"/>
            <ProcedimentosRelacionados xsi:type="sei:ArrayOfProcedimentoRelacionado" SOAP-ENC:arrayType="xsd:string[]"/>
            <UnidadesEnvio xsi:type="sei:ArrayOfIdUnidade" SOAP-ENC:arrayType="xsd:string[]"/>
            <SinManterAbertoUnidade xsi:type="xsd:string"></SinManterAbertoUnidade>
            <SinEnviarEmailNotificacao xsi:type="xsd:string"></SinEnviarEmailNotificacao>
            <DataRetornoProgramado xsi:type="xsd:string"></DataRetornoProgramado>
            <DiasRetornoProgramado xsi:type="xsd:string"></DiasRetornoProgramado>
            <SinDiasUteisRetornoProgramado xsi:type="xsd:string"></SinDiasUteisRetornoProgramado>
            <IdMarcador xsi:type="xsd:string"></IdMarcador>
            <TextoMarcador xsi:type="xsd:string"></TextoMarcador>
        </sei:gerarProcedimento>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_POST, true);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8'));

        $response = curl_exec($curlHandler);
        $erroCurl = curl_error($curlHandler);
        $codigoHttp = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        curl_close($curlHandler);

        $this->assertEmpty($erroCurl, 'Falha na chamada SOAP gerarProcedimento: ' . $erroCurl);
        $this->assertEquals(200, $codigoHttp, 'A chamada SOAP gerarProcedimento nao retornou HTTP 200.');
        $this->assertNotEmpty($response, 'A resposta da chamada SOAP gerarProcedimento veio vazia.');

        $procedimentoFormatado = $this->extrairProcedimentoFormatado($response);
        $this->assertNotEmpty(
            $procedimentoFormatado,
            'Nao foi possivel extrair o ProcedimentoFormatado da resposta SOAP: ' . $response
        );

        return $procedimentoFormatado;
    }

    /**
     * Extrai o valor de ProcedimentoFormatado da resposta SOAP, independentemente do prefixo de namespace.
     */
    protected function extrairProcedimentoFormatado($response)
    {
        $padrao = '/<(?:[\w-]+:)?ProcedimentoFormatado[^>]*>(.*?)<\/(?:[\w-]+:)?ProcedimentoFormatado>/s';
        if (!preg_match($padrao, $response, $partes)) {
            return null;
        }

        $valor = str_replace(array('<![CDATA[', ']]>'), '', $partes[1]);
        return trim($valor);
    }

    protected function selecionarUnidadeInterna($unidadeDestino)
    {
        PaginaTeste::selecionarUnidadeContexto($this, $unidadeDestino);
    }

    protected function sairSistema()
    {
        $this->paginaBase->sairSistema();
    }

    public static function generateRandomString($length = 10)
    {
        return substr(
            str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / 62))),
            1,
            $length
        );
    }
}
