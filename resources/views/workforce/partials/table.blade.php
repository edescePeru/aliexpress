<div class="table-responsive">

    <table class="table table-sm table-hover">

        <thead>

        <tr>

            <th>
                Servicio
            </th>

            <th>
                Unidad
            </th>

            <th class="text-right">
                Precio
            </th>

            <th class="text-center">
                Facturable
            </th>

            <th
                    class="text-center"
                    style="width:120px;"
            >
                Acciones
            </th>

        </tr>

        </thead>

        <tbody>

        @forelse(
            $workforces
            as $workforce
        )

            <tr>

                <td>
                    {{ $workforce->description }}
                </td>

                <td>

                    {{ optional(
                        $workforce->unitMeasure
                    )->name ?? '-' }}

                </td>

                <td class="text-right">

                    {{ number_format(
                        $workforce->unit_price,
                        2
                    ) }}

                </td>

                <td class="text-center">

                    @if($workforce->billable)

                        <span class="badge badge-success">
                            Sí
                        </span>

                    @else

                        <span class="badge badge-secondary">
                            No
                        </span>

                    @endif

                </td>

                <td class="text-center">
                    @can('manage_workforce')
                        <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-edit-workforce="{{ $workforce->id }}"
                        >
                            <i class="fas fa-edit"></i>
                        </button>

                        <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-delete-workforce="{{ $workforce->id }}"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    @endcan

                </td>

            </tr>

        @empty

            <tr>

                <td
                        colspan="5"
                        class="text-center text-muted py-4"
                >
                    No se encontraron servicios.
                </td>

            </tr>

        @endforelse

        </tbody>

    </table>

</div>


@if(
    $workforces->lastPage() > 1
)

    <div class="d-flex justify-content-between align-items-center">

        <small class="text-muted">

            Mostrando
            {{ $workforces->firstItem() }}
            -
            {{ $workforces->lastItem() }}
            de
            {{ $workforces->total() }}

        </small>

        <nav>

            <ul class="pagination pagination-sm mb-0">

                @for(
                    $page = 1;
                    $page <= $workforces->lastPage();
                    $page++
                )

                    <li
                            class="page-item
                        {{ $page === $workforces->currentPage()
                            ? 'active'
                            : ''
                        }}"
                    >

                        <button
                                type="button"
                                class="page-link"
                                data-workforce-page="{{ $page }}"
                        >
                            {{ $page }}
                        </button>

                    </li>

                @endfor

            </ul>

        </nav>

    </div>

@endif