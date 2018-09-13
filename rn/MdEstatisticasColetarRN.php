<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasColetarRN extends InfraRN
{

    public function __construct() {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function coletarIndicadores() {
        try {

            $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
            $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla', false, '');

            $ind = array();

            $ind['dataColeta'] = $this->obterDataColeta();
            $ind['orgaoSigla'] = $orgaoSigla;
            $ind['seiVersao'] = $this->obterVersaoSEI();
            $ind['phpVersao'] = $this->obterVersaoPHP();
            $ind['memcachedVersao'] = $this->obterVersaoMemcached();
            $ind['solrVersao'] = $this->obterVersaoSolr();
            $ind['protocolo'] = $this->obterProtocolo();
            $ind['quantidadeUnidades'] = $this->obterQuantidadeUnidades();
            $ind['quantidadeProcedimentos'] = $this->obterQuantidadeProcessosAdministrativos();
            $ind['quantidadeUsuarios'] = $this->obterQuantidadeUsuarios();
            $ind['quantidadeDocumentosInternos'] = $this->obterQuantidadeDocumentosInternos();
            $ind['quantidadeDocumentosExternos'] = $this->obterQuantidadeDocumentosExternos();
            $ind['quantidadeMemoria'] = $this->obterUsoMemoria();
            $ind['porcentagemCPU'] = $this->obterUsoCPU();
            $ind['espacoDiscoUsado'] = $this->obterEspacoDisco();
            $ind['estrategiaCessao'] = $this->obterEstrategiaCessao();
            $ind['tamanhoDatabase'] = $this->obterTamanhoDataBase();
            $ind['bancoSei'] = $this->obterTipoSGBD();
            $ind['bancoVersao'] = $this->obterBancoVersao();
            $ind['servidorAplicacao'] = $this->obterServidorAplicacao();
            $ind['sistemaOperacional'] = $this->obterSistemaOperacional();
            $ind['sistemaOperacionalDetalhado'] = $this->obterSistemaOperacionalDetalhado();
            $ind['tamanhoFilesystem'] = $this->obterTamanhoFileSystem();
            $ind['tabelasTamanhos'] = $this->obterTamanhoTabelas();
            $ind['modulos'] = $this->obterPlugins();
            $ind['extensoes'] = $this->obterQuantidadeDocumentosExternosPorExtensao();
            $ind['anexosTamanhos'] = $this->obterTamanhoDocumentosExternos();

            return $ind;
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro processando estatísticas do sistema.', $e);
        }
    }

    private function obterVersaoSEI() {
        return SEI_VERSAO;
    }

    private function obterVersaoPHP() {
        return phpversion();
    }

    private function getDirectorySize($path) {
        $bytestotal = 0;
        $path = realpath($path);
        if ($path !== false) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal;
    }

    private function obterTamanhoFileSystem() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        if ($objConfiguracaoSEI->isSetValor('SEI', 'RepositorioArquivos')) {
            $diretorio = $objConfiguracaoSEI->getValor('SEI', 'RepositorioArquivos');
            $tamanho = $this->getDirectorySize($diretorio);
        }
        return $tamanho;
    }

    private function obterPlugins() {
        global $SEI_MODULOS;
        $lista = array();
        foreach ($SEI_MODULOS as $strModulo => $seiModulo) {
            $result = array(
                'nome' => $strModulo,
                'versao' => $seiModulo->getVersao()
            );
            array_push($lista, $result);
        }

        return $lista;
    }

    private function obterQuantidadeUnidades() {
        $objUnidadeRN = new UnidadeRN();
        return $objUnidadeRN->contarRN0128(new UnidadeDTO());
    }

    private function obterTamanhoTotalDocumentosExternos() {
        $query = "select sum(tamanho) as tamanho from anexo where sin_ativo = 'S'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;
        return $tamanho;
    }

    private function obterQuantidadeUsuarios() {
        $query = "SELECT COUNT(*) as quantidade FROM usuario WHERE sin_ativo = 'S'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade;
    }

    private function obterProtocolo() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        if ($objConfiguracaoSEI->isSetValor('SessaoSEI', 'https')) {
            $temHTTPS = $objConfiguracaoSEI->getValor('SessaoSEI', 'https');
            $protocolo = 'HTTP';
            if ($temHTTPS) {
                $protocolo = 'HTTPS';
            }
            return $protocolo;
        }
    }

    private function obterQuantidadeProcessosAdministrativos() {
        $query = "select count(*) as quantidade from procedimento";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade;
    }

    private function obterTipoSGBD() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        return $objConfiguracaoSEI->getValor('BancoSEI', 'Tipo', false, '');
    }

    private function obterQuantidadeDocumentosInternos() {
        $query = "SELECT COUNT(*) as quantidade FROM documento WHERE STA_DOCUMENTO = 'I'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade;
    }

    private function obterQuantidadeDocumentosExternos() {
        $query = "SELECT COUNT(*) as quantidade FROM documento WHERE STA_DOCUMENTO = 'X'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade;
    }

    private function obterQuantidadeDocumentosExternosPorExtensao() {
        $query = "SELECT nome FROM anexo WHERE sin_ativo = 'S'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $extensoes = array();
        // Calculando na aplicacao para funcionar independente do banco
        foreach ($rs as $r) {
            $extensao = pathinfo($r['nome'], PATHINFO_EXTENSION);
            $qtd = $extensoes[$extensao];
            if (! $qtd) {
                $qtd = 0;
            }
            $extensoes[$extensao] = $qtd + 1;
        }
        $lista = array();
        foreach ($extensoes as $key => $value) {
            $result = array(
                'extensao' => $key,
                'quantidade' => $value
            );
            array_push($lista, $result);
        }
        return $lista;
    }

    private function obterEstrategiaCessao() {
        return ini_get('session.save_handler');
    }

    private function obterVersaoMemcached() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $host = $objConfiguracaoSEI->getValor('CacheSEI', 'Servidor', false, '');
        $porta = $objConfiguracaoSEI->getValor('CacheSEI', 'Porta', false, '');

        $memcache = new Memcache();
        $memcache->connect($host, $porta);
        $versao = $memcache->getVersion();

        return $versao;
    }

    private function obterTamanhoDatabase() {
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'MySql') {
            $query = "SELECT table_schema, SUM(data_length + index_length) as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei' GROUP BY table_schema";
        } elseif ($sgbd == 'SqlServer') {
            $query = "SELECT SUM(Total_Pages * 8 * 1000) As tamanho FROM sys.partitions As P INNER JOIN sys.allocation_units As A ON P.hobt_id = A.container_id  INNER JOIN sys.tables t on t.object_id = p.object_id";
        } elseif ($sgbd == 'Oracle') {
            $query = "";
        }
        $rs = array();
        if ($query) {
            $rs = BancoSEI::getInstance()->consultarSql($query);
        }
        $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;
        return $tamanho;
    }

    private function obterTamanhoTabelas() {
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'MySql') {
            $query = "SELECT table_name as tabela, data_length + index_length as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei'";
        } elseif ($sgbd == 'SqlServer') {
            $query = "" . " SELECT t.name as tabela,  SUM(Total_Pages * 8 * 1000) As tamanho " . " FROM sys.partitions As P " . "   INNER JOIN sys.allocation_units As A ON P.hobt_id = A.container_id " . "   INNER JOIN sys.tables t on t.object_id = p.object_id " . " GROUP BY t.name ORDER BY t.name";
        } elseif ($sgbd == 'Oracle') {
            $query = "";
        }
        $tabelas = array();
        if ($query) {
            $tabelas = BancoSEI::getInstance()->consultarSql($query);
        }
        return $tabelas;
    }

    private function obterVersaoSolr() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $url = $objConfiguracaoSEI->getValor('Solr', 'Servidor', false, 'http://localhost:8983/solr');
        $url = $url . '/admin/info/system?wt=json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        $json = json_decode($output, true);
        return $json['lucene']['lucene-spec-version'];
    }

    private function obterServidorAplicacao() {
        return $_SERVER['SERVER_SOFTWARE'];
    }

    private function obterSistemaOperacional() {
        return PHP_OS;
    }

    private function obterSistemaOperacionalDetalhado() {
        return php_uname();
    }

    private function obterDataColeta() {
        return date(DATE_ATOM);
    }

    private function obterTamanhoDocumentosExternos() {
        $resultado = array();
        // 0MB - !MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 0 AND tamanho < 1000";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[0] = array(
            'tamanho' => '0MB - 1MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // 1MB - 10MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 1000 AND tamanho < 10000";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[1] = array(
            'tamanho' => '1MB - 10MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // 10MB - 100MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 10000 AND tamanho < 100000";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[2] = array(
            'tamanho' => '10MB - 100MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // > 100MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 100000";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[3] = array(
            'tamanho' => 'Maior que 100MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );
        return $resultado;
    }

    private function obterUsoMemoria() {
        return memory_get_usage();
    }

    private function obterUsoCPU() {
        $load = sys_getloadavg();
        $uso = null;
        if ($load) {
            $uso = $load[0];
        }
        return $uso;
    }

    private function obterEspacoDisco() {
        if (php_uname('s') == 'Windows NT') {
            $unidade = substr($_SERVER['DOCUMENT_ROOT'], 0, 2);
            if (! $unidade) {
                $unidade = 'C:';
            }
        } else {
            $unidade = "/";
        }
        $total = disk_total_space($unidade);
        $free = disk_free_space($unidade);
        return $total - $free;
    }

    private function obterBancoVersao() {
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'MySql') {
            $query = "SELECT version() as versao";
        } elseif ($sgbd == 'SqlServer') {
            $query = "SELECT SERVERPROPERTY('productversion') as versao";
        } elseif ($sgbd == 'Oracle') {
            $query = "select version AS versao from product_component_version WHERE product LIKE 'Oracle%'";
        }
        $rs = array();
        if ($query) {
            $rs = BancoSEI::getInstance()->consultarSql($query);
        }
        $versao = (count($rs) && isset($rs[0]['versao'])) ? $rs[0]['versao'] : null;
        return $versao;
    }

    public function obterVelocidadePorCidade() {
        $query = "
      select d.nome as cidade, e.nome as uf, avg(velocidade) as velocidade
      from velocidade_transferencia a
        join unidade b on b.id_unidade = a.id_unidade
        join contato c on b.id_contato = c.id_contato
        join cidade d on c.id_cidade = d.id_cidade
        join uf e on d.id_uf = e.id_uf
      group by
        d.nome, e.nome
    ";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $lista = array();
        foreach ($rs as $r) {
            $result = array(
                'cidade' => utf8_encode($r['cidade']),
                'uf' => utf8_encode($r['uf']),
                'velocidade' => $r['velocidade']
            );

            array_push($lista, $result);
        }
        return $lista;
    }

    public function obterAcessosUsuarios($ultimadata = null) {
        if ($ultimadata == null) {
            $ultimadata = "1900-01-01";
        }
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'MySql') {
            $query = "select count(*) as quantidade, date(dth_acesso) as data from infra_navegador where date(dth_acesso) > '%s' group by date(dth_acesso)";
        } elseif ($sgbd == 'SqlServer') {
            $query = "select count(*) as quantidade, CONVERT(date, dth_acesso) as data from infra_navegador where dth_acesso >= '%s' group by CONVERT(date, dth_acesso)";
        } elseif ($sgbd == 'Oracle') {
            $query = "select count(*) as quantidade, to_char(dth_acesso,'YYYY-MM-DD') AS data from infra_navegador where dth_acesso >= date '%s' group by to_char(dth_acesso,'YYYY-MM-DD')";
        }
        
        $rs = array();
        if ($query) {
            $query = sprintf($query, $ultimadata);
            $rs = BancoSEI::getInstance()->consultarSql($query);
        }
        return $rs;
    }

    public function obterSistemasOperacionaisUsuarios() {
        $sgbd = $this->obterTipoSGBD();
        if ($sgbd == 'Oracle') {
            $query = "select distinct to_char(user_agent) as nome from infra_auditoria where user_agent is not null";
        } else {
            $query = "select distinct user_agent as nome from infra_auditoria where user_agent is not null";
        }
        $sistemas = BancoSEI::getInstance()->consultarSql($query);

        $lista = array();
        foreach ($sistemas as $r) {
            $texto = $r['nome'];
            $inicio = strpos($texto, '(');
            if ($inicio !== false) {
                $fim = strpos($texto, ')', $inicio);
                $nome = substr($texto, $inicio + 1, $fim - $inicio - 1);
                array_push($lista, $nome);
            }
        }
        $lista = array_unique($lista);

        $sistemas = array();
        foreach ($lista as $n) {
            $result = array(
                'nome' => $n
            );
            array_push($sistemas, $result);
        }
        return $sistemas;
    }

    public function obterNavegadores() {
        $query = "select count(*) as quantidade, identificacao as nome, versao from infra_navegador group by identificacao,versao";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $lista = array();
        foreach ($rs as $r) {
            $result = array(
                'nome' => utf8_encode($r['nome']),
                'quantidade' => $r['quantidade'],
                'versao' => $r['versao']
            );
            array_push($lista, $result);
        }
        return $lista;
    }

    public function obterQuantidadeRecursos($dataultimorecurso) {
        if ($dataultimorecurso == null) {
            $dataultimorecurso = "1900-01-01";
        }
        $current_month = date("Y-m-01");
        $sgbd = $this->obterTipoSGBD();
        if ($sgbd == 'MySql') {
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, recurso, count(*) as quantidade FROM sei.infra_auditoria where date(dth_acesso) > '%s' and date(dth_acesso) < '%s' group by 1, 2, 3 order by 1, 2, 3";
        } elseif ($sgbd == 'SqlServer') {
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, recurso, count(*) as quantidade FROM infra_auditoria where dth_acesso > '%s' and dth_acesso < '%s' group by year(dth_acesso), month(dth_acesso), recurso order by 1, 2, 3";            
        } elseif ($sgbd == 'Oracle'){
            $query = "SELECT to_char(dth_acesso, 'YYYY') AS ano, to_char(dth_acesso, 'MM') AS mes, recurso, count(*) as quantidade FROM sei.infra_auditoria WHERE dth_acesso > date '%s'  AND dth_acesso < date '%s' GROUP BY to_char(dth_acesso, 'YYYY'), to_char(dth_acesso, 'MM'), recurso";
        }
        if ($query) {
            $query = sprintf($query, $dataultimorecurso, $current_month);
            return BancoSEI::getInstance()->consultarSql($query);
        }
    }

    public function obterQuantidadeLogErro() {
        $sgbd = $this->obterTipoSGBD();
        if ($sgbd == 'MySql') {
            $query = "select year(dth_log) ano, month(dth_log) mes, week(dth_log) + 1 semana, count(*) as quantidade from sei.infra_log where sta_tipo = 'E' group by 1, 2, 3";
        } elseif ($sgbd == 'SqlServer') {
            $query = "select year(dth_log) ano, month(dth_log) mes, datepart(week, dth_log) semana, count(*) as quantidade from infra_log where sta_tipo = 'E' group by year(dth_log), month(dth_log), datepart(week, dth_log)";
        } elseif ($sgbd == 'Oracle'){
            $query = "select to_char(dth_log, 'YYYY') AS ano, to_char(dth_log, 'MM') AS mes, to_char(dth_log, 'WW') AS semana, count(*) as quantidade from sei.infra_log where sta_tipo = 'E' GROUP BY to_char(dth_log, 'YYYY'), to_char(dth_log, 'MM'), to_char(dth_log, 'WW')";
        }
        if ($query) {
            return BancoSEI::getInstance()->consultarSql($query);
        }
    }
}
?>
