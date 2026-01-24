var dragged;

    function dragStart(event) {
        dragged = event.target;
        event.dataTransfer.setData("text/plain", event.target.dataset.stopid);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        var table = document.querySelector('.current_stops_table tbody');
    
        table.addEventListener('dragstart', function(event) {
            dragged = event.target.closest('tr'); // Получаем ближайшую строку, содержащую перетаскиваемый элемент
            draggedIndex = Array.from(table.children).indexOf(dragged); // Сохраняем порядковый номер перетаскиваемой строки
        });
    
        table.addEventListener('dragover', function(event) {
            event.preventDefault(); // Предотвращаем обработку события dragover по умолчанию
        });
    
        table.addEventListener('drop', function(event) {
            event.preventDefault(); // Предотвращаем обработку события drop по умолчанию
            var dropRow = event.target.closest('tr'); // Получаем ближайшую строку, перед которой нужно вставить перетаскиваемую строку
            var dropIndex = Array.from(table.children).indexOf(dropRow); // Получаем порядковый номер строки, перед которой нужно вставить перетаскиваемую строку
    
            // Обновляем порядковые номера элементов в таблице
            if (dragged && dragged !== dropRow) {
                // Вставляем перетаскиваемую строку в таблицу перед целевым местом вставки
                if (draggedIndex < dropIndex) {
                    table.insertBefore(dragged, dropRow.nextSibling);
                } else {
                    table.insertBefore(dragged, dropRow);
                }
    
                // Обновляем значения порядковых номеров в элементах input
                var rows = table.querySelectorAll('tr');
                rows.forEach(function(row, index) {
                    var stopNumInput = row.querySelector('.edit_stop_num');
                    if (stopNumInput) {
                        stopNumInput.value = index + 2;
                    }
                });
            }
        });
    });
    

    
    
    
