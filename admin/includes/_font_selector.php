<?php
// Componente reutilizable para el selector de fuentes.
// Espera que se definan dos variables antes de incluirlo:
// $line_number: (int) El número de la línea (1-4)
// $selected_font: (string) El nombre de la fuente actualmente seleccionada para esa línea.
// $todas_las_fuentes: (array) La lista de todas las fuentes disponibles.
?>
<select name="fuente<?= $line_number ?>">
    <?php foreach ($todas_las_fuentes as $fuente): ?>
        <option value="<?= htmlspecialchars($fuente) ?>" 
                style="font-family: '<?= htmlspecialchars($fuente) ?>', sans-serif; font-size: 16px;" 
                <?= ($selected_font == $fuente) ? "selected" : "" ?>>
            <?= htmlspecialchars($fuente) ?>
        </option>
    <?php endforeach; ?>
</select>

