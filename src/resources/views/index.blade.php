@extends('layouts.app_admin')
@section('content')
    <div class="container-fluid p-0">
        <h1 class="h3 mb-3 d-none d-md-inline-flex">Imports</h1>
        <a href="{{ route('importsxml.add') }}" class="btn btn-primary-emissor float-end mt-n1 px-md-4 fw-bold">
            <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" focusable="false" role="img" class="bi-sm bi bi-plus-lg me-2"><path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 0 1 0-2h6V1a1 1 0 0 1 1-1z"></path></svg>
            Cadastrar
        </a>
        <div class="row">
            <div class="col-md-3">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Filtros</h5>
                        <form action="{{ route('importsxml.index') }}" method="get">
                            <input type="hidden" name="controle" value='Pesquisar'>
                            <div class="row">
                                <div class="col-md-12">
                                    <label>Data Início:</label>
                                    <input required type="date" name='dtInc' id='dtInc' class="form-control" placeholder="Data de Início" value="{{ $dtInc }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <label>Data Fim:</label>
                                    <input required type="date" name='dtFn' id='dtFn' class="form-control" placeholder="Data de Início" value="{{ $dtFn }}">
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <input type="submit" value="Pesquisar" class="btn btn-primary">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-body vld-parent table-responsive">
                        <h5 class="card-title text-info text-center mb-4">Clique sobre o import para ver os detalhes.</h5>
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Import</th>
                                    <th>Arquivo</th>
                                    <th>Status</th>
                                    <th>Andamento</th>
                                    <th>Retorno</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($arrayImports as $import)
                                    <tr onclick="mostraDetalhes({{ $import['ID'] }})">
                                        <td>{{ $import['created_at'] }}</td>
                                        <td>{{ $import['TP_IMPORT'] }}</td>
                                        <td>{{ $import['TP_ARQUIVO'] }}</td>
                                        <td>{{ $import['STATUS'] }}</td>
                                        <td>{{ $import['ANDAMENTO'] }}</td>
                                        <td>{{ $import['RETORNO'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
    function mostraDetalhes(id_import){
        window.location.href = "{{ route('importsxml.view') }}/" + id_import;
    }
    </script>
@endsection
