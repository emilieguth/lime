<?php
namespace user;

/**
 * For admins
 *
 */
class AdminUi {

	public function __construct() {

		\Asset::css('user', 'admin.css');

	}

	/**
	 * Get navigation in identities admin
	 *
	 */
	public function getNavigation(string $selection): string {

		$h = '<div class="nav">';

			$h .= '<a href="/user/admin/" class="nav-link '.($selection === 'user' ? 'active' : '').'">'.s("Parcourir").'</a>';

		if(\Feature::get('user\ban')) {
			$h .= '<a href="/user/admin/ban" class="nav-link '.($selection === 'ban' ? 'active' : '').'">'.s("Bannissements").'</a>';
		}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form with default conditions
	 *
	 * @param array $condition
	 * @param int $count
	 * @return string
	 */
	public function getUsersForm(array $condition, int $count) {

		\Asset::css('util', 'form.css');
		\Asset::js('util', 'form.js');

		$h = '<div id="form-backend">';
		$h .= '<form><input type="number" data-field="id" class="form-control" value="'.encode($condition['id']).'" placeholder="'.s("ID").'"/></form>';
		$h .= '<form><input type="text" data-field="lastName" class="form-control" value="'.encode($condition['lastName']).'" placeholder="'.s("Nom").'"/></form>';
		$h .= '<form><input type="text" data-field="email" class="form-control" value="'.encode($condition['email']).'" placeholder="'.s("E-mail").'"/></form>';
		$h .= '<form><label><input type="checkbox" data-field="active" value="1" '.($condition['active'] ? 'checked="checked"' : '').'/> '.s("Actifs seulement").'</label></form>';
		$h .= '<a href="/user/admin/" class="btn btn-secondary">'.\Asset::icon('x').'</a>';

		$h .= '<span>'.p("{value} utilisateur", "{value} utilisateurs", $count).'</span>';

		$h .= '</div>';

		return $h;

	}

	/**
	 * Organize the table with the users
	 *
	 */
	public function displayUsers(\Collection $cUser, int $nUser, int $page, string $order, bool $isExternalConnected): string {

		if($nUser === 0) {
			return '<div class="util-info">'.s("Il n'y a aucun utilisateur à afficher...").'</div>';
		}

		$h = '<table>';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.\util\TextUi::order($order, 'id', '#', '-').'</th>';
					$h .= '<th>'.\util\TextUi::order($order, 'lastName', s("Nom")).' / '.\util\TextUi::order($order, 'email', s("E-mail")).'</th>';
					$h .= '<th>'.s("Rôle").'</th>';
					$h .= '<th>'.s("Inscription").'</th>';
					$h .= '<th>'.\util\TextUi::order($order, 'ping', s("Connexion"), '-').'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
			foreach($cUser as $eUser) {

				$connectionOptions = [
					'class' => 'dropdown-item'
				];
				if($isExternalConnected) {
					$connectionOptions['class'] .= ' disabled';
					$connectionTitle = s("Déconnectez vous de ce compte pour pouvoir vous reconnecter sur un autre compte.");
				} else {
					$connectionOptions['data-ajax'] = '/user/log:doLoginExternal';
					$connectionOptions['post-user'] = $eUser['id'];
					$connectionTitle = s("Se connecter sur ce compte");
				}

				$auth = [];
				foreach($eUser['auths'] as $eUserAuth) {
					switch($eUserAuth['type']) {
						case 'basic':
							$auth[] = \Asset::icon('envelope', ['title' => s("Email")]);
							break;
						case 'imap':
							$auth[] = \Asset::icon('at', ['title' => s("Imap")]);
							break;
						default:
							$auth[] = ucfirst($eUserAuth['type']);
					}
				}
				$h .= '<tr class="user-admin-status-'.$eUser['status'].'">';
					$h .= '<td class="text-center">'.$eUser['id'].'</td>';
					$h .= '<td>';
						$h .= '<a href="'.UserUi::url($eUser).'">'.encode($eUser['firstName']).' <b>'.encode($eUser['lastName']).'</b></a><br />';
						$h .= '<small>'.encode($eUser['email']).'</small>';
					$h .= '</td>';
					$h .= '<td>'.$eUser['role']['name'].'</td>';
					$h .= '<td>';
						$h .= \util\DateUi::numeric($eUser['createdAt'], \util\DateUi::DATE);
					$h .= '</td>';
					$h .= '<td>'.\util\DateUi::numeric($eUser['ping'], \util\DateUi::DATE).'</td>';
					$h .= '<td class="text-center">';
						$h .= '<div>';
							$h .= '<a class="dropdown-toggle btn btn-secondary" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<a data-ajax="/user/admin/:update" post-id="'.$eUser['id'].'" class="dropdown-item">';
									$h .= s("Modifier l'utilisateur");
								$h .= '</a>';
								$h .= '<a '.attrs($connectionOptions).' title="'.$connectionTitle.'">';
									$h .= s("Se connecter en tant que...");
								$h .= '</a> ';
							$h .= '</div>';
						$h .= '</div>';
					$h .= '</td>';
				$h .= '</tr>';
			}
			$h .= '</tbody>';
		$h .= '</table>';

		$h .= \util\TextUi::pagination($page, $nUser / 100);

		return $h;
	}

	public function updateUser(User $eUser): \Panel {

		$form = new \util\FormUi(['style' => 'horizontal']);

		$h = $form->openAjax('/user/admin/:doUpdate');

			$h .= $form->hidden('id', $eUser['id']);

			$h .= $form->dynamicGroups($eUser, ['email', 'firstName', 'lastName', 'birthdate']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: encode($eUser['firstName'].' '.$eUser['lastName']),
			body: $h,
			close: 'reload'
		);

	}

}
?>
