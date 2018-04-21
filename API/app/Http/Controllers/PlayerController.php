<?php

namespace App\Http\Controllers;

use App\Player;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;

class PlayerController extends Controller
{
	public function __construct() {
		$this->middleware('auth', ['only' => [
			'info',
			'updatePlayer',
			'deletePlayer',
		]]);
	}

	/* Get all players */
	public function index() {
		$players = Player::all();
		return response()->json($players, 200);
	}

	/* Get player by id */
	public function getPlayer($id){
		$Player = Player::find($id);
		return response()->json($Player, 200);
	}

	/* Create a player (POST) */
	public function createPlayer(Request $request) {
		// Check if username is available
		$username = $request->input('username');
		if (Player::isUsernameAvailable($username)) {
			$player = Player::create($request->all());
			$player->password = Hash::make($request->input('password'));
			$player->save();
			return response()->json($player, 200);
		} else {
			return response()->json(['status' => 'fail', 'message' => "Ce nom d'utilisateur existe déjà."], 409);
		}
	}
	
	/* Update a player (PUT) by id */
	public function updatePlayer(Request $request, $id) {
		// On récupère le joueur correspondant à l'id en paramètre
		$player = Player::find($id);

		// On vérifie que l'utilisateur modifie sa propre page
		if ($player != Auth::user()) {
			return response()->json(['status' => 'fail', 'message' => "Vous n'avez pas les droits pour effectuer cette modification."], 401);
		}

		// On met à jour ses infos
		if ($request->input('username')) 
			$player->username = $request->input('username');
		if ($request->input('password'))
			$player->password = Hash::make($request->input('password'));
		if ($request->input('firstname'))
			$player->firstname = $request->input('firstname');
		if ($request->input('lastname'))
			$player->lastname = $request->input('lastname');
		if ($request->input('birthdate'))
			$player->birthdate = $request->input('birthdate');
		if ($request->input('mail'))
			$player->mail = $request->input('mail');

		// On enregistre
		$player->save();

		// On retourne le joueur modifié
		return response()->json($player, 200);
	}

	/* Delete Player */
	public function deletePlayer($id) {
		// On récupère le joueur correspondant à l'id en paramètre 
		$player = Player::find($id);

		// On vérifie que l'utilisateur supprime sa propre page (sinon : message d'erreur)
		if ($player != Auth::user()) {
			return response()->json(['status' => 'fail', 'message' => "Vous n'avez pas les droits pour supprimer cet utilisateur."], 401);
		}

		// Suppression du joueur
		$player->delete();

		// On renvoie un message de validation 
		return response()->json(['status' => 'success', 'message' => "Le profil a bien été supprimé."], 200);
	}

	/* Renvoie l'utilisateur connecté */
	public function info() {
    	return response()->json(Auth::user(), 200);
    }
}