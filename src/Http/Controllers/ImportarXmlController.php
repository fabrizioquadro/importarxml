<?php
namespace fabrizioquadro\importarxml\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
/*
Models que já são do sistema e estão no namespace App\Models
*/
use App\Models\Empresa;
use App\Models\Pessoa;
use App\Models\Item;
use App\Models\Grupo_Item;
use App\Models\Tipo_Imposto;
use App\Models\Imposto;
use App\Models\Endereco;

/*
Models que são especificas do  pacote
*/
use fabrizioquadro\importarxml\Models\Import_Arquivo;
use fabrizioquadro\importarxml\Models\Import;

use fabrizioquadro\importarxml\Jobs\DesconpactaXmlImportJob;
use fabrizioquadro\importarxml\Jobs\InsereXmlDbJob;
use fabrizioquadro\importarxml\Jobs\AnalisaXmlProdutoSericoJob;


class ImportarXmlController extends Controller{

    public $ID_EMPRESA;

    public function __construct(){
        $this->ID_EMPRESA = 1;
    }

    public function index(Request $request){

        if($request->get('controle') == 'Pesquisar'){
            $imports = Import::where('ID_EMPRESA', $this->ID_EMPRESA)
            ->where('created_at','>=',$request->get('dtInc')." 00:00:00")
            ->where('created_at','<=',$request->get('dtFn')." 23:59:59")
            ->orderByDesc('created_at')
            ->get();

            $dtInc = $request->get('dtInc');
            $dtFn = $request->get('dtFn');
        }
        else{
            $imports = Import::where('ID_EMPRESA', $this->ID_EMPRESA)->orderByDesc('created_at')->get();
            $dtInc = null;
            $dtFn = null;
        }

        $arrayImports = array();

        foreach($imports as $import){
            $array = array();

            $var = explode(' ', $import->created_at);

            $array['created_at'] = $this->dataDbForm($var[0])." ".$var[1];
            $array['TP_IMPORT'] = $import->TP_IMPORT;
            $array['ID'] = $import->ID;
            $array['TP_ARQUIVO'] = $import->TP_ARQUIVO;
            $array['STATUS'] = $import->STATUS;
            $array['ANDAMENTO'] = $import->ANDAMENTO;
            $array['RETORNO'] = $import->RETORNO;

            $arrayImports[] = $array;
        }

        return view('importarxml::index', compact('arrayImports','dtInc','dtFn'));
    }

    public function add(){
        return view('importarxml::add');
    }

    public function store(Request $request){
        if($request->hasFile('arquivo') && $request->file('arquivo')->isValid()){

            try{
                $empresa = Empresa::where('ID', $this->ID_EMPRESA)->first();
                $nmPasta = date('YmdHis');
                $extensao = strtolower($request->arquivo->getClientOriginalExtension());

                $request->arquivo->move(public_path("importsXML/".$empresa->ID."/".$nmPasta), $request->arquivo->getClientOriginalName());
                $tp_import = $request->get('tp_import');

                $dados = [
                    'ID_EMPRESA' => $empresa->ID,
                    'TP_IMPORT' => $tp_import,
                    'PASTA' => $nmPasta,
                    'NM_ARQUIVO' => $request->arquivo->getClientOriginalName(),
                    'TP_ARQUIVO' => $extensao,
                    'STATUS' => "Processando",
                ];

                if($extensao == "xml"){
                    $dados['ANDAMENTO'] = "Em Fila de Processamento";
                }
                elseif($extensao == "zip"){
                    $dados['ANDAMENTO'] = "Em Fila de Descompactação";
                }

                $import = Import::create($dados);

                if($import){
                    $ID_IMPORT = $import->id;
                    DesconpactaXmlImportJob::dispatch();
                }

                if($extensao == "xml"){
                    $dados_arq = [
                        'ID_IMPORT' => $ID_IMPORT,
                        'ID_EMPRESA' => $import->ID_EMPRESA,
                        'ARQUIVO' => $request->arquivo->getClientOriginalName(),
                        'STATUS' => 'Fila de Processamento',
                    ];

                    $import_arq = Import_Arquivo::create($dados_arq);

                    InsereXmlDbJob::dispatch($ID_IMPORT);

                }

            }
            catch(\Exception $e){
                //vamos deletar a pasta do import se algo deu errado
                unlink(public_path("importsXML/".$empresa->ID."/".$nmPasta."/".$request->arquivo->getClientOriginalName()));
                rmdir(public_path("importsXML/".$empresa->ID."/".$nmPasta));

                @Import_Arquivo::where('ID_IMPORT', $ID_IMPORT)->delete();

                @Import::where('ID', $ID_IMPORT)->delete();

                //$mensagem = "Ocorreu um erro na sua solicitação de import ERRO:".$e->getMessage()." Repasse o erro para o administrador para solução do problema.";
                //return redirect('/')->with('mensagem', $mensagem);
            }
        }
    }

    public function dataDbForm($data){
        $data = explode("-", $data);
	    $data = $data[2]."/".$data[1]."/".$data[0];
	    return $data;
    }

    public function view($id = null){
        $import = Import::where('ID', $id)->first();

        $arquivos = Import_Arquivo::where('ID_IMPORT', $import->ID)->get();

        $var = explode(' ',$import->created_at);
        $dtHrImport = $this->dataDbForm($var[0])." ".$var[1];

        return view('importarxml::view', compact('import','arquivos','dtHrImport'));
    }
}
