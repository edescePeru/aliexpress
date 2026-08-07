<div class="form-group">

    <label
            for="{{ $prefix }}_code"
    >
        Código
        <span class="badge badge-danger">
            *
        </span>
    </label>

    <input
            type="text"
            id="{{ $prefix }}_code"
            name="code"
            class="form-control"
            maxlength="50"
            required
    >

</div>


<div class="form-group">

    <label
            for="{{ $prefix }}_name"
    >
        Nombre
        <span class="badge badge-danger">
            *
        </span>
    </label>

    <input
            type="text"
            id="{{ $prefix }}_name"
            name="name"
            class="form-control"
            maxlength="100"
            required
    >

</div>


<div class="form-group">

    <label
            for="{{ $prefix }}_max_active_users"
    >
        Máximo de usuarios activos
    </label>

    <input
            type="number"
            id="{{ $prefix }}_max_active_users"
            name="max_active_users"
            class="form-control"
            min="1"
            step="1"
            value="1"
            required
    >

</div>


<div class="form-group">

    <label
            for="{{ $prefix }}_description"
    >
        Descripción
    </label>

    <textarea
            id="{{ $prefix }}_description"
            name="description"
            class="form-control"
            rows="3"
            maxlength="1000"
    ></textarea>

</div>