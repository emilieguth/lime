<?php
new JsonView('editor', function($data, AjaxTemplate $t) {

	$t->push('title', s("Aide sur l'éditeur de texte"));

	$h = '<p>'.s("Notre éditeur de texte est un outil puissant, qui vous laisse une grande liberté d'expression tout en vous aidant naturellement à écrire de beaux textes.").'</p>';

	$h .= '<h3>'.s("Fonctionnalités").'</h3>';

	$h .= '<table class="editor-features">';
		$h .= '<tr>';
			$h .= '<td>'.s("Sélectionnez du texte pour appliquer un style (gras, italique, souligné), transformer en titre ou ajouter un lien.").'</td>';
			$h .= '<td>'.\Asset::image('editor', 'help-selection.png').'</td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td>'.s("Sur une ligne vide, cliquez sur le + pour ajouter une image, une vidéo, un séparateur ou encore un conseil.").'</td>';
			$h .= '<td>'.\Asset::image('editor', 'help-line.png').'</td>';
		$h .= '</tr>';
	$h .= '</table>';

	$h .= '<h3>'.s("Raccourcis clavier").'</h3>';

	$h .= '<table class="editor-shortcuts">';
		$h .= '<tr>';
			$h .= '<td><kbd>Ctrl</kbd> + <kbd>b</kbd></td>';
			$h .= '<td><b>'.s("Gras").'</b></td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>Ctrl</kbd> + <kbd>i</kbd></td>';
			$h .= '<td><i>'.s("Italique").'</i></td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>Ctrl</kbd> + <kbd>u</kbd></td>';
			$h .= '<td><u>'.s("Souligné").'</u></td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>Ctrl</kbd> + <kbd>k</kbd></td>';
			$h .= '<td>'.s("Insérer un lien").' <small>'.s("(sur un texte sélectionné uniquement)").'</small></td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>Ctrl</kbd> + <kbd>s</kbd></td>';
			$h .= '<td>'.s("Enregistrer le brouillon").'</td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>*</kbd> <kbd>espace</kbd></td>';
			$h .= '<td>'.s("Liste à point").'</td>';
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<td><kbd>1</kbd> <kbd>.</kbd> <kbd>espace</kbd></td>';
			$h .= '<td>'.s("Liste à numéro").'</td>';
		$h .= '</tr>';
	$h .= '</table>';

	$h .= '<br/>';

	$h .= '<h3>'.s("Smileys").'</h3>';

	$list = [
		':)' => '😀',
		':(' => '😦',
		';)' => '😉',
		':\'(' => '😥',
		':p' => '😛',
		'(sleep)' => '😴',
		'(angry)' => '😡',
		'(a)' => '😇',
		'(yes)' => '👍',
		'(no)' => '👎',
		'(l)' => '💗',
		'(k)' => '😘'
	];

	$h .= '<div class="editor-smileys">';

		foreach($list as $keyboard => $smiley) {
			$h .= '<div>'.$keyboard.'</div>';
			$h .= '<div>'.$smiley.'</div>';
		}

	$h .= '</div>';

	$t->push('body', $h);

});
?>
