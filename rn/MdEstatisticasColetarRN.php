<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasColetarRN extends InfraRN
{

    public function __construct() {
        parent::__construct();

        //Qd SqlServer, vamos tentar setar o timeout
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        $sgbd = $objConfiguracaoSEI->getValor('BancoSEI', 'Tipo', false, '');
        if ($sgbd == 'SqlServer') {
            ini_set('mssql.timeout', 60 * 10);
        }
    }

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();

    }

    public function coletarIndicadores() {
        try {

            $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
            $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas', 'sigla', false, '');

            $objUsuarioRN = new UsuarioRN();
            $objUsuarioRN->setStrStaTipo(array(UsuarioRN::$TU_EXTERNO, UsuarioRN::$TU_SIP));

            $ind = array();
            InfraDebug::getInstance()->gravar("Obtendo Data de Coleta", InfraLog::$INFORMACAO);
            $ind['dataColeta'] = $this->obterDataColeta();
            $ind['orgaoSigla'] = $orgaoSigla;
            InfraDebug::getInstance()->gravar("Obtendo Versoes", InfraLog::$INFORMACAO);
            $ind['seiVersao'] = $this->obterVersaoSEI();
            $ind['phpVersao'] = $this->obterVersaoPHP();
            $ind['memcachedVersao'] = $this->obterVersaoMemcached();
            $ind['solrVersao'] = $this->obterVersaoSolr();
            InfraDebug::getInstance()->gravar("Obtendo Protocolo", InfraLog::$INFORMACAO);
            $ind['protocolo'] = $this->obterProtocolo();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de Unidades", InfraLog::$INFORMACAO);
            $ind['quantidadeUnidades'] = $this->obterQuantidadeUnidades();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de Procs", InfraLog::$INFORMACAO);
            $ind['quantidadeProcedimentos'] = $this->obterQuantidadeProcessosAdministrativos();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de Usuarios", InfraLog::$INFORMACAO);
            $ind['quantidadeUsuarios'] = $this->obterQuantidadeUsuarios();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de Usuarios Interno", InfraLog::$INFORMACAO);
            $ind['quantidadeUsuariosInterno'] = $this->obterQuantidadeUsuariosInterno();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de Usuarios Externo", InfraLog::$INFORMACAO);
            $ind['quantidadeUsuariosExterno'] = $this->obterQuantidadeUsuariosExterno();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de DocsInternos", InfraLog::$INFORMACAO);
            $ind['quantidadeDocumentosInternos'] = $this->obterQuantidadeDocumentosInternos();
            InfraDebug::getInstance()->gravar("Obtendo Qtd de DocsExternos", InfraLog::$INFORMACAO);
            $ind['quantidadeDocumentosExternos'] = $this->obterQuantidadeDocumentosExternos();
            $ind['quantidadeMemoria'] = $this->obterUsoMemoria();
            $ind['porcentagemCPU'] = $this->obterUsoCPU();
            InfraDebug::getInstance()->gravar("Obtendo Espaco Disco", InfraLog::$INFORMACAO);
            $ind['espacoDiscoUsado'] = $this->obterEspacoDisco();
            $ind['estrategiaCessao'] = $this->obterEstrategiaCessao();
            InfraDebug::getInstance()->gravar("Obtendo Dados DB", InfraLog::$INFORMACAO);
            $ind['tamanhoDatabase'] = $this->obterTamanhoDataBase();
            $ind['bancoSei'] = $this->obterTipoSGBD();
            $ind['bancoVersao'] = $this->obterBancoVersao();
            $ind['servidorAplicacao'] = $this->obterServidorAplicacao();
            $ind['sistemaOperacional'] = $this->obterSistemaOperacional();
            $ind['sistemaOperacionalDetalhado'] = $this->obterSistemaOperacionalDetalhado();
            InfraDebug::getInstance()->gravar("Obtendo Tamanho FileSystem. Pode demorar varios minutos. Caso o tempo esteja muito exagerado leia o README sobre como pular este indicador ou como usar um comando alternativo para a leitura", InfraLog::$INFORMACAO);
            $ind['tamanhoFilesystem'] = $this->obterTamanhoFileSystem();
            InfraDebug::getInstance()->gravar("Obtendo Tamanho Tables", InfraLog::$INFORMACAO);
            $ind['tabelasTamanhos'] = $this->obterTamanhoTabelas();
            InfraDebug::getInstance()->gravar("Obtendo Modulos", InfraLog::$INFORMACAO);
            $ind['modulos'] = $this->obterPlugins();
            InfraDebug::getInstance()->gravar("Obtendo Qtd Docs Extensao - Pode demorar alguns minutos", InfraLog::$INFORMACAO);
            $ind['extensoes'] = $this->obterQuantidadeDocumentosExternosPorExtensao();
            InfraDebug::getInstance()->gravar("Obtendo Tamanho Docs Externos", InfraLog::$INFORMACAO);
            $ind['anexosTamanhos'] = $this->obterTamanhoDocumentosExternos();
            $ind['isMonoOrgao'] = $this->obterSeMonoOrgao();

            return $ind;
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro processando estatísticas do sistema.', $e);
        }
    }

    private static function bolArrFindItem($arrNeedle, $strHaystack){
        $r=false;
        foreach ($arrNeedle as $v) {
            if(strstr($strHaystack, $v)) return true;
        }
        return $r;
    }

    private $IG = array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php');

    private static function getDirContents($dir, $ignorar = array(), &$results = array()){

        $files = scandir($dir);

        foreach($files as $key => $value){

            if($value == "." || $value == "..") continue;

            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!MdEstatisticasColetarRN::bolArrFindItem($ignorar, $path)){
                if(!is_dir($path)) {
                    $results[] = $path;

                } else {
                    MdEstatisticasColetarRN::getDirContents($path, $ignorar, $results);
                }
            }
        }

        return $results;
    }

    public function obterHashs(){

        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
        try{
            //buscar arquivos a ignorar na elaboracao do hash
            $ignore_files = $objConfiguracaoSEI->getValor('MdEstatisticas', 'ignorar_arquivos');
            if(!is_array($ignore_files)) throw new Exception('nao array');
        }catch(Exception $e){
            $ignore_files = array('sei/temp', 'sei/config/ConfiguracaoSEI.php', 'sei/config/ConfiguracaoSEI.exemplo.php', '.vagrant', '.git');
        }

        $a = MdEstatisticasColetarRN::getDirContents(DIR_SEI_CONFIG . '/../../', $ignore_files);

        if ($objConfiguracaoSEI->isSetValor('SEI','Modulos')){

            foreach($objConfiguracaoSEI->getValor('SEI','Modulos') as $strModulo => $strPathModulo){
                $reflectionClass = new ReflectionClass($strModulo);
                $classe = $reflectionClass->newInstance();
                $arrModulos[$strModulo] = array('modulo' => $strModulo, 'path' => $strPathModulo, 'versao' => $classe->getVersao());
            }
        }

        foreach ($a as $key => $value) {
            $m="";
            $version="";

            foreach ($arrModulos as $k => $v) {
                if(strpos($value, 'web/modulos/'.$arrModulos[$k]['path']) !== false){
                    $m = $k;
                    $version = $arrModulos[$k]['versao'];
                    break;
                }
            }

            //vamos retirar a parte inicial do dir que nao interessa
            $novo_valor = $value;
            $pos=MdEstatisticasColetarRN::bolArrFindItem(array('infra/infra', 'sei/', 'sip/'), $novo_valor);
            if($pos !== false){
                $novo_valor = substr($novo_valor, $pos);
            }

            $hash = '';
            if($value) $hash = hash_file('sha256', $value);
            $b[] = array('file' => $novo_valor,
                         'hash' => $hash,
                         'modulo' => $m,
                         'versaoModulo' => $version,
                         'versaoSei' => SEI_VERSAO);
        }

        return $b;

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
            try{
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    try{
                        $bytestotal += $object->getSize();
                    }catch(Exception $e1){
                        $bytestotal += 0;
                    }
                }
            }catch(Exception $e2){
                $bytestotal = 0;
            }
        }
        return $bytestotal;
    }

    private function obterTamanhoFileSystem() {
        $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();

        if ($objConfiguracaoSEI->isSetValor('SEI', 'RepositorioArquivos')) {
            $diretorio = $objConfiguracaoSEI->getValor('SEI', 'RepositorioArquivos');
            $usarDuLinux = $objConfiguracaoSEI->getValor('MdEstatisticas', 'filesystemdu', false, '');
            $ignoreReading = $objConfiguracaoSEI->getValor('MdEstatisticas', 'ignorarLeituraAnexos', false, '');
            $tamanhofs = $objConfiguracaoSEI->getValor('MdEstatisticas', 'tamanhoFs', false, '');

            if($ignoreReading=="true"){
                if(!is_numeric($tamanhofs)) $tamanhofs = 0;
                $tamanho = $tamanhofs;
            }elseif($usarDuLinux){
                $tamanho = shell_exec ("du -s -b " . $diretorio);
                preg_match_all('!\d+!', $tamanho, $arrSize);
                $tamanho = $arrSize[0][0];
                if(!is_numeric($tamanho)) $tamanho = 0;
            }else{
                $tamanho = $this->getDirectorySize($diretorio);
            }

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
        $query = "SELECT COUNT(*) as quantidade FROM usuario WHERE sin_ativo = 'S' AND sta_tipo = '".UsuarioRN::$TU_SIP."' OR sta_tipo = '".UsuarioRN::$TU_EXTERNO."'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade;
    }

    private function obterQuantidadeUsuariosInterno() {
      $query = "SELECT COUNT(*) as quantidade FROM usuario WHERE sin_ativo = 'S' AND sta_tipo = '".UsuarioRN::$TU_SIP."'";
      $rs = BancoSEI::getInstance()->consultarSql($query);
      $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
      return $quantidade;
    }

    private function obterQuantidadeUsuariosExterno() {
      $query = "SELECT COUNT(*) as quantidade FROM usuario WHERE sin_ativo = 'S' AND sta_tipo = '".UsuarioRN::$TU_EXTERNO."'";
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
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'Oracle') {
            $query = "SELECT SUBSTR(nome,LENGTH(nome)-4+1,4) nome, count(*) quantidade FROM anexo WHERE sin_ativo = 'S' group by SUBSTR(nome,LENGTH(nome)-4+1,4)";
        }else{
            $query = "SELECT right(nome,4) nome, count(*) quantidade FROM anexo WHERE sin_ativo = 'S' group by right(nome, 4)";
        }

        $rs = BancoSEI::getInstance()->consultarSql($query);
        $extensoes = array();
        foreach ($rs as $r)  {
            $extensao = $this->extrairExtensao($r['nome']);
            $qtd = $extensoes[$extensao];
            if (! $qtd) {
                $qtd = 0;
            }
            $extensoes[$extensao] = $qtd + $r['quantidade'];
        }
        // Calculando na aplicacao para funcionar independente do banco
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

    private function extrairExtensao($filename) {
        $listaarq = explode('.', $filename);
        $extensao = end($listaarq);
        return utf8_encode($extensao);
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
            $query = "SELECT table_name as tabela, coalesce(data_length,0) + coalesce(index_length,0) as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei'";
        } elseif ($sgbd == 'SqlServer') {
            $query = "" . " SELECT t.name as tabela,  SUM(ISNULL(Total_Pages,0) * 8 * 1024) As tamanho " . " FROM sys.partitions As P " . "   INNER JOIN sys.allocation_units As A ON P.hobt_id = A.container_id " . "   INNER JOIN sys.tables t on t.object_id = p.object_id " . " GROUP BY t.name ORDER BY t.name";
        } elseif ($sgbd == 'Oracle') {
            $query =    "select tabela, sum(tamanho_tabela) + sum(tamanho_indice) as tamanho
                        from
                        (
                            SELECT
                            segment_name as tabela,
                            SUM(nvl(bytes,0)) as tamanho_tabela,
                            0 as tamanho_indice
                            from
                            USER_SEGMENTS
                            WHERE SEGMENT_TYPE='TABLE'
                            GROUP BY segment_name

                            union all

                            select
                            ui.table_name as tabela,
                            0 as tamanho_tabela,
                            sum(nvl(bytes,0)) as tamanho_indice
                            from
                            user_segments us inner join
                            user_indexes ui on ui.index_name = us.segment_name
                            group by
                            ui.table_name

                        ) tudo
                        group by tabela";
        }
        $tabelas = array();
        if ($query) {
            try{
                $tabelas = BancoSEI::getInstance()->consultarSql($query);
            }catch(Exception $e){
                $tabelas = array();
            }
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
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 0 AND tamanho < 1048576";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[0] = array(
            'tamanho' => '0MB - 1MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // 1MB - 10MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 1048576 AND tamanho < 10485760";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[1] = array(
            'tamanho' => '1MB - 10MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // 10MB - 100MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 10485760 AND tamanho < 104857600";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $resultado[2] = array(
            'tamanho' => '10MB - 100MB',
            'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
        );

        // > 100MB
        $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 104857600";
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
            try{
                $rs = BancoSEI::getInstance()->consultarSql($query);
            }catch(Exception $e){
                $rs = array(array('versao' => 'Undefined'));
            }
        }
        $versao = (count($rs) && isset($rs[0]['versao'])) ? $rs[0]['versao'] : null;
        return $versao;
    }

    public function obterVelocidadePorCidade() {
        if (InfraUtil::compararVersoes(SEI_VERSAO, "<", "4.1.0")) {
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
            $query = "select distinct STR(user_agent) as nome from infra_auditoria where user_agent is not null";
        }
        try{
            $sistemas = BancoSEI::getInstance()->consultarSql($query);
        } catch (Exception $e) {
            $sistemas = array(array('nome'=>'(XX)'));
        }

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

    public function obterNavegadores($ultimadata = null) {
        if ($ultimadata == null) {
          $ultimadata = "1900-01-01";
        }
        $current_month = date("Y-m-01");
        $sgbd = $this->obterTipoSGBD();
        $query = '';
        if ($sgbd == 'MySql') {
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, identificacao as nome, versao, count(*) as quantidade from infra_navegador where date(dth_acesso) > '%s' and date(dth_acesso) < '%s' group by 1, 2, 3, 4 order by 1,2,3,4";
        } elseif ($sgbd == 'SqlServer') {
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, identificacao as nome, versao, count(*) as quantidade from infra_navegador where dth_acesso > '%s' and dth_acesso < '%s' group by year(dth_acesso), month(dth_acesso), identificacao, versao order by 1,2,3,4";
        } elseif ($sgbd == 'Oracle'){
            $query = "SELECT to_char(dth_acesso, 'YYYY') AS ano, to_char(dth_acesso, 'MM') AS mes, identificacao as nome, versao, count(*) as quantidade from infra_navegador WHERE dth_acesso > date '%s'  AND dth_acesso < date '%s' group by to_char(dth_acesso, 'YYYY'), to_char(dth_acesso, 'MM'), identificacao, versao order by to_char(dth_acesso, 'YYYY'), to_char(dth_acesso, 'MM'), identificacao, versao";
        }
        $lista = array();
        if ($query) {
          $query = sprintf($query, $ultimadata, $current_month);
          //echo $query;
          $rs = BancoSEI::getInstance()->consultarSql($query);
          foreach ($rs as $r) {
              $result = array(
                  'nome' => utf8_encode($r['nome']),
                  'quantidade' => $r['quantidade'],
                  'versao' => $r['versao'],
                  'ano' => $r['ano'],
                  'mes' => $r['mes'],
              );
              array_push($lista, $result);
          }

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
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, recurso, count(*) as quantidade FROM infra_auditoria where date(dth_acesso) > '%s' and date(dth_acesso) < '%s' group by 1, 2, 3 order by 1, 2, 3";
        } elseif ($sgbd == 'SqlServer') {
            $query = "SELECT year(dth_acesso) as ano, month(dth_acesso) as mes, recurso, count(*) as quantidade FROM infra_auditoria where dth_acesso > '%s' and dth_acesso < '%s' group by year(dth_acesso), month(dth_acesso), recurso order by 1, 2, 3";
        } elseif ($sgbd == 'Oracle'){
            $query = "SELECT to_char(dth_acesso, 'YYYY') AS ano, to_char(dth_acesso, 'MM') AS mes, recurso, count(*) as quantidade FROM infra_auditoria WHERE dth_acesso > date '%s'  AND dth_acesso < date '%s' GROUP BY to_char(dth_acesso, 'YYYY'), to_char(dth_acesso, 'MM'), recurso";
        }
        if ($query) {
            $query = sprintf($query, $dataultimorecurso, $current_month);
            return BancoSEI::getInstance()->consultarSql($query);
        }
    }

    public function obterQuantidadeLogErro() {
        $sgbd = $this->obterTipoSGBD();
        if ($sgbd == 'MySql') {
            $query = "select year(dth_log) ano, month(dth_log) mes, week(dth_log) + 1 semana, count(*) as quantidade from infra_log where sta_tipo = 'E' group by 1, 2, 3";
        } elseif ($sgbd == 'SqlServer') {
            $query = "select year(dth_log) ano, month(dth_log) mes, datepart(week, dth_log) semana, count(*) as quantidade from infra_log where sta_tipo = 'E' group by year(dth_log), month(dth_log), datepart(week, dth_log)";
        } elseif ($sgbd == 'Oracle'){
            $query = "select to_char(dth_log, 'YYYY') AS ano, to_char(dth_log, 'MM') AS mes, to_char(dth_log, 'WW') AS semana, count(*) as quantidade from infra_log where sta_tipo = 'E' GROUP BY to_char(dth_log, 'YYYY'), to_char(dth_log, 'MM'), to_char(dth_log, 'WW')";
        }
        if ($query) {
            return BancoSEI::getInstance()->consultarSql($query);
        }
    }

    public function obterSeMonoOrgao () {
        $query = "SELECT count(*) as quantidade FROM orgao WHERE sin_ativo = 'S'";
        $rs = BancoSEI::getInstance()->consultarSql($query);
        $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;
        return $quantidade <= 1;
    }
}
?>
