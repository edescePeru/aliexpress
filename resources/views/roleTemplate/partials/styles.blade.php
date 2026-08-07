<style>

    .permission-module-card {
        height: auto;
        margin-bottom: 15px;
    }

    .permission-module-card .card-header {
        cursor: pointer;
        padding: 0.65rem 0.8rem;
    }

    .permission-module-card .card-body {
        padding: 0.8rem;
    }

    .permission-module-name {
        font-weight: 600;
    }

    .permission-module-counter {
        font-size: 11px;
    }

    .permission-item {
        margin-bottom: 8px;
    }

    .permission-item:last-child {
        margin-bottom: 0;
    }

    .permission-code {
        display: block;
        margin-left: 24px;
        color: #868e96;
        font-size: 10px;
        word-break: break-word;
    }

    .role-template-action-bar {
        position: sticky;
        bottom: 0;
        z-index: 1000;

        margin-left: -20px;
        margin-right: -20px;
        margin-bottom: -20px;
        margin-top: 25px;

        padding: 12px 10px;

        background: #ffffff;
        border-top: 1px solid #dee2e6;

        box-shadow:
                0 -2px 7px rgba(0, 0, 0, 0.08);
    }

    @media (max-width: 767.98px) {

        .role-template-action-bar {
            margin-left: -10px;
            margin-right: -10px;
        }

        .role-template-action-bar .btn {
            padding-left: 12px;
            padding-right: 12px;
        }

    }

</style>