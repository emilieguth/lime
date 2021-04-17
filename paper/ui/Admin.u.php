<?php
namespace paper;

/**
 * For admins
 *
 */
class AdminUi {

	public function __construct() {

		\Asset::js('paper', 'admin.js');

	}

	public function getNavigation(string $selection): string {

		$pages = [
			'forum' => s("Parcourir"),
			'abuses' => s("Abus"),
		];

		$h = '<div class="nav">';

		foreach($pages as $page => $name) {
			$h .= '<a href="/paper/admin/'.($page === 'forum' ? '' : $page).'" class="nav-link '.($selection === $page ? 'active' : '').'">'.$name.'</a>';
		}

		$h .= '</div>';

		return $h;

	}


	/**
	 * Form for a new forum
	 *
	 */
	public function create(): \Panel {

		$form = new \util\FormUi(['style' => 'horizontal']);

		$h = $form->openAjax('/paper/admin/forum:doCreate');

		$h .= $this->write($form, new Forum());

		$h .= $form->group(
			content: $form->submit(s("Créer le forum"))
		);

		$h .= $form->close();

		return new \Panel(
			title: s("Créer un forum"),
			body: $h,
			close: 'reload'
		);

	}

	/**
	 * Form to update an existing forum
	 *
	 */
	public function update(Forum $eForum): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/admin/forum:doUpdate');
		$h .= $form->hidden('id', $eForum['id']);

		$h .= $this->write($form, $eForum);

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour"))
		);

		$h .= $form->close();

		return new \Panel(
			title: encode($eForum['name']),
			body: $h,
			close: 'reload'
		);

	}

	protected function write(\util\FormUi $form, Forum $eForum): string {

		$h = $form->group(
			s("Nom du forum"),
			$form->text('name', $eForum['name'] ?? NULL)
		);

		$h .= $form->group(
			s("Description du forum"),
			$form->textarea('description', $eForum['description'] ?? NULL, ['data-limit' => \Setting::get('descriptionSizeMax')])
		);

		return $h;

	}

	/**
	 * Display a list of communities of a user
	 *
	 * @param \Collection $cForum
	 */
	public function getForums(\Collection $cForum): string {

		\Asset::lib('util', 'Sortable-1.11.0.js');

		$h = '';

		if($cForum->count() === 0) {

			$h .= '<p class="util-info">'.s("Vous n'avez créé encore aucun forum.").'</p>';
			$h .= '<div><a class="btn btn-secondary" href="/paper/admin/forum:create">'.s("Ajouter un forum").'</a></div>';

			return $h;

		}

		$h .= $this->getForumsList($cForum);
		$h .= $this->createNewForum();


		return $h;

	}

	protected function getForumsList(\Collection $cForum): string {

		$h = '<div class="forum-admin-table" onrender="AdminPaper.makeForumsSortable()">';

		if($cForum->count() > 0) {

			foreach($cForum as $eForum) {

				$h .= '<div id="forum-admin-'.$eForum['id'].'" data-id="'.$eForum['id'].'" class="forum-admin-box">';
				$h .= $this->getForumAdmin($eForum);
				$h .= '</div>';

			}

		}

		$h .= '</div>';

		return $h;

	}

	public function getForumAdmin(Forum $eForum): string {

		$h = (new ForumUi())->getItem($eForum);

		if($eForum['active']) {
			$statusAttributes = [
				'title' => s("Forum actif"),
				'class' => 'color-success'
			];
		} else {
			$statusAttributes = [
				'title' => s("Forum inactif"),
				'class' => 'color-muted'
			];
		}

		$status = \Asset::icon('circle-fill', $statusAttributes);

		$lines[] = s("Créé le {date}", ['date' => \util\DateUi::numeric($eForum['createdAt'], \util\DateUi::DATE)]);
		$lines[] = '<a href="/paper/admin/forum:update?id='.$eForum['id'].'">'.s("Configurer").'</a>';

		if($eForum['deletedAt'] === NULL) {
			$lines[] = s("Statut du forum : {value}", ['value' => '<a data-ajax="/paper/admin/forum:doActive" post-id="'.$eForum['id'].'">'.$status.'</a>']);
		}

		if($eForum['active'] === FALSE) {

			$link = function(Forum $eForum, $text) {
				return '<a data-ajax="/paper/admin/forum:doDelete" post-id="'.$eForum['id'].'">'.$text.'</a>';
			};

			if($eForum['deletedAt']) {
				$lines[] = s("Ce forum a été supprimé, il sera définitivement effacé le {date}", ['date' => \util\DateUi::numeric($eForum['deletedAt'], \util\DateUi::DATE)]).' ('.$link($eForum, s("annuler")).')';
			} else {
				$lines[] = $link($eForum, s("Supprimer le forum"));
			}
		}

		$h .= '<div>'.implode(' | ', $lines).'</div>';

		return $h;

	}

	protected function createNewForum(): string {

		$h = '<br/>';
		$h .= '<div>
			<a class="btn btn-secondary" href="/paper/admin/forum:create">'.s("Ajouter un forum").'</a>
		</div>';

		return $h;

	}

}
?>
