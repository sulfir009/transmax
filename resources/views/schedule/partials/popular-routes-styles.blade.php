<style>
    .mt_schedule_scope .mt_schedule_popular {
        padding: 40px 0 20px;
    }

    .mt_schedule_scope .mt_schedule_popular_title {
        font-size: 20px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 18px;
    }

    .mt_schedule_scope .mt_schedule_popular_grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .mt_schedule_scope .mt_schedule_popular_card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 12px 26px rgba(20, 24, 57, 0.08);
        padding: 18px 20px;
    }

    .mt_schedule_scope .mt_schedule_popular_card_title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .mt_schedule_scope .mt_schedule_popular_list {
        display: grid;
        gap: 6px;

        /* ✅ чтобы "все маршруты" не делали карточку бесконечной */
        max-height: 320px;
        overflow: auto;
        padding-right: 6px;
    }

    .mt_schedule_scope .mt_schedule_popular_item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        font-size: 13px;
    }

    .mt_schedule_scope .mt_schedule_popular_item a {
        color: #ff7a00;
        text-decoration: none;
    }

    .mt_schedule_scope .mt_schedule_popular_item a:hover {
        text-decoration: underline;
    }

    .mt_schedule_scope .mt_schedule_popular_price {
        font-weight: 600;
        color: #2c3163;
        white-space: nowrap;
    }

    /* ✅ на больших экранах 3 колонки удобнее */
    @media (min-width: 1200px) {
        .mt_schedule_scope .mt_schedule_popular_grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .mt_schedule_scope .mt_schedule_popular_grid {
            grid-template-columns: 1fr;
        }
    }
</style>
