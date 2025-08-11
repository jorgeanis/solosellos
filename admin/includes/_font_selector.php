<?php
// Componente reutilizable para el selector de fuentes.
// Espera que se definan dos variables antes de incluirlo:
// $line_number: (int) El número de la línea (1-4)
// $selected_font: (string) El nombre de la fuente actualmente seleccionada para esa línea.
?>
<select name="fuente<?= $line_number ?>">
    <option value="Roboto" class="font-Roboto" <?= ($selected_font == "Roboto") ? "selected" : "" ?>>Roboto</option>
    <option value="Open Sans" class="font-Open-Sans" <?= ($selected_font == "Open Sans") ? "selected" : "" ?>>Open Sans</option>
    <option value="Lato" class="font-Lato" <?= ($selected_font == "Lato") ? "selected" : "" ?>>Lato</option>
    <option value="Montserrat" class="font-Montserrat" <?= ($selected_font == "Montserrat") ? "selected" : "" ?>>Montserrat</option>
    <option value="Poppins" class="font-Poppins" <?= ($selected_font == "Poppins") ? "selected" : "" ?>>Poppins</option>
    <option value="Raleway" class="font-Raleway" <?= ($selected_font == "Raleway") ? "selected" : "" ?>>Raleway</option>
    <option value="Merriweather" class="font-Merriweather" <?= ($selected_font == "Merriweather") ? "selected" : "" ?>>Merriweather</option>
    <option value="Nunito" class="font-Nunito" <?= ($selected_font == "Nunito") ? "selected" : "" ?>>Nunito</option>
    <option value="Oswald" class="font-Oswald" <?= ($selected_font == "Oswald") ? "selected" : "" ?>>Oswald</option>
    <option value="Ubuntu" class="font-Ubuntu" <?= ($selected_font == "Ubuntu") ? "selected" : "" ?>>Ubuntu</option>
    <option value="PT Sans" class="font-PT-Sans" <?= ($selected_font == "PT Sans") ? "selected" : "" ?>>PT Sans</option>
    <option value="Quicksand" class="font-Quicksand" <?= ($selected_font == "Quicksand") ? "selected" : "" ?>>Quicksand</option>
    <option value="Work Sans" class="font-Work-Sans" <?= ($selected_font == "Work Sans") ? "selected" : "" ?>>Work Sans</option>
    <option value="Bebas Neue" class="font-Bebas-Neue" <?= ($selected_font == "Bebas Neue") ? "selected" : "" ?>>Bebas Neue</option>
    <option value="Archivo" class="font-Archivo" <?= ($selected_font == "Archivo") ? "selected" : "" ?>>Archivo</option>
    <option value="Fira Sans" class="font-Fira-Sans" <?= ($selected_font == "Fira Sans") ? "selected" : "" ?>>Fira Sans</option>
    <option value="Playfair Display" class="font-Playfair-Display" <?= ($selected_font == "Playfair Display") ? "selected" : "" ?>>Playfair Display</option>
    <option value="Rubik" class="font-Rubik" <?= ($selected_font == "Rubik") ? "selected" : "" ?>>Rubik</option>
    <option value="Caveat" class="font-Caveat" <?= ($selected_font == "Caveat") ? "selected" : "" ?>>Caveat</option>
    <option value="Dancing Script" class="font-Dancing-Script" <?= ($selected_font == "Dancing Script") ? "selected" : "" ?>>Dancing Script</option>
    <option value="Shadows Into Light" class="font-Shadows-Into-Light" <?= ($selected_font == "Shadows Into Light") ? "selected" : "" ?>>Shadows Into Light</option>
    <option value="Satisfy" class="font-Satisfy" <?= ($selected_font == "Satisfy") ? "selected" : "" ?>>Satisfy</option>
    <option value="Great Vibes" class="font-Great-Vibes" <?= ($selected_font == "Great Vibes") ? "selected" : "" ?>>Great Vibes</option>
    <option value="Permanent Marker" class="font-Permanent-Marker" <?= ($selected_font == "Permanent Marker") ? "selected" : "" ?>>Permanent Marker</option>
    <option value="Patrick Hand" class="font-Patrick-Hand" <?= ($selected_font == "Patrick Hand") ? "selected" : "" ?>>Patrick Hand</option>
    <option value="Gloria Hallelujah" class="font-Gloria-Hallelujah" <?= ($selected_font == "Gloria Hallelujah") ? "selected" : "" ?>>Gloria Hallelujah</option>
    <option value="Indie Flower" class="font-Indie-Flower" <?= ($selected_font == "Indie Flower") ? "selected" : "" ?>>Indie Flower</option>
    <option value="Fredoka" class="font-Fredoka" <?= ($selected_font == "Fredoka") ? "selected" : "" ?>>Fredoka</option>
    <option value="Josefin Sans" class="font-Josefin-Sans" <?= ($selected_font == "Josefin Sans") ? "selected" : "" ?>>Josefin Sans</option>
    <option value="Amatic SC" class="font-Amatic-SC" <?= ($selected_font == "Amatic SC") ? "selected" : "" ?>>Amatic SC</option>
</select>
