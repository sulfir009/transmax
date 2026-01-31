<?php echo view('layout.components.header.head', [
    'page_data' => $page_data ?? [],
    'pageData' => $pageData ?? [],
])->render(); ?>
