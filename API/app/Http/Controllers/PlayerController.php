<?php

namespace App\Http\Controllers;

use App\Player;
use App\Team;
use App\Match;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use DB;
use DateTime;
use DateInterval;

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
		foreach ($players as $player) {
			$player->goals;
			$player->teams;
		}
		return response()->json($players, 200);
	}

	/* Get player by id */
	public function getPlayer($id){
		$player = Player::find($id);
		if (!$player) {
			return response()->json([
				"status"=>"error",
				"message"=>"Le joueur n'existe pas."
			], 404);
		}
		$goals = $player->goals;
		$teams = $player->teams;
		foreach ($teams as $team) {
			$team->players;
		}

		// Get number of matches played by the player
		$played_matches_count = Match::join('match_team', 'matches.id', '=', 'match_team.match_id')
			->join('teams', 'match_team.team_id', '=', 'teams.id')
			->join('team_player', 'teams.id', '=', 'team_player.team_id')
			->join('players', 'team_player.player_id', '=', 'players.id')
			->where('players.id', $player->id)
			->select('matches.*')
			->distinct()
			->get()
			->count();

		// Get number of victories
		$victories_count = Match::join('match_team', 'matches.id', '=', 'match_team.match_id')
			->join('teams', 'match_team.team_id', '=', 'teams.id')
			->join('team_player', 'teams.id', '=', 'team_player.team_id')
			->join('players', 'team_player.player_id', '=', 'players.id')
			->where([
				['players.id', $player->id],
				['match_team.winner', 1],
			])
			->select('matches.*')
			->distinct()
			->get()
			->count();

		// Get the 3 player's team (with most victories)
		$best_teams = Team::join('match_team', 'teams.id', '=', 'match_team.team_id')
			->join('team_player', 'team_player.team_id', '=', 'teams.id')
			->where([
				['team_player.player_id', $player->id],
				['match_team.winner', 1],
			])
			->select('id', 'name', DB::raw('count(*) as victories_count'))
			->groupBy("teams.id")
			->orderBy("victories_count", 'desc')
			->limit(3)
			->get();

		foreach ($best_teams as $team) {
			$team->players;
		}

		// Get the role of the player where he scores the most
		$goals_as_striker = count($goals->where("role", "striker"));
		$goals_as_defender = count($goals->where("role", "defender"));
		$best_role = ($goals_as_striker || $goals_as_defender) ? ($goals_as_striker >= $goals_as_defender ? "striker" : "defender") : "";

		// Get the last match of the player
		$last_match = Match::join('match_team', 'matches.id', '=', 'match_team.match_id')
			->join('teams', 'match_team.team_id', '=', 'teams.id')
			->join('team_player', 'teams.id', '=', 'team_player.team_id')
			->join('players', 'team_player.player_id', '=', 'players.id')
			->where('players.id', $player->id)
			->where('matches.end_time', '!=', '0000-00-00 00:00:00')
			->select('matches.*')
			->orderBy('end_time', 'desc')
			->first();

		if ($last_match) {
			$last_match->goals;
			$last_match->teams;
			foreach ($last_match->teams as $team) {
				$team->players;
			}
		}
		
		// Calculate the total time played by a player
		$matches = Match::join('match_team', 'matches.id', '=', 'match_team.match_id')
			->join('teams', 'match_team.team_id', '=', 'teams.id')
			->join('team_player', 'teams.id', '=', 'team_player.team_id')
			->join('players', 'team_player.player_id', '=', 'players.id')
			->where('players.id', $player->id)
			->where('matches.end_time', '!=', '0000-00-00 00:00:00')
			->select('matches.end_time', 'matches.created_at')
			->get();
		
		$played_time = new DateInterval('PT0H');
		foreach($matches as $match) {
			$datetime1 = new DateTime($match->end_time);
			$datetime2 = new DateTime($match->created_at);
			$datetime2->add(new DateInterval('PT2H'));
			$interval = $datetime1->diff($datetime2);
			$e = new DateTime('00:00');
			$f = clone $e;
			$e->add($played_time);
			$e->add($interval);
			$played_time = $f->diff($e);
		}
		
		return response()->json([
			"player" => $player,
			"goals_count" => count($goals),
			"gamelles_count" => count($goals->where("gamelle", 1)),
			"played_matches_count" => $played_matches_count,
			"victories_count" => $victories_count,
			"defeats_count" => $played_matches_count - $victories_count,
			"best_teams" => $best_teams,
			"best_role" => $best_role,
			"goals_as_striker" => $goals_as_striker,
			"goals_as_defender" => $goals_as_defender,
			"last_match" => $last_match,
			"played_time" => $played_time->format("00%Y-%M-%D %H:%I:%S")
		], 200);
	}

	/* Search player by username, firstname or lastname */
	public function searchPlayers(Request $request){
		$this->validate($request, [
			'value' => 'required',
		]);
		$value = $request->input('value');
		$players = Player::where('username', 'like', '%'.$value.'%')
			->orWhere('firstname', 'like', '%'.$value.'%')
			->orWhere('lastname', 'like', '%'.$value.'%')
			->orWhere(DB::raw('CONCAT(firstname, " ", lastname)'), 'like', '%'.$value.'%')
			->orWhere(DB::raw('CONCAT(lastname, " ", firstname)'), 'like', '%'.$value.'%')
			->get();

		if (count($players) == 0) {
			return response()->json([
				"status"=>"error",
				"message"=>"Aucun joueur ne correspond à votre recherche"
			], 404);
		}
		
		return response()->json([
			"players" => $players,
		], 200);
	}

	/* Create a player */
	public function createPlayer(Request $request) {
		$this->validate($request, [
			'photo' => 'image|mimes:jpeg,jpg,png,gif,svg|max:2048',
			'mail' => 'email',
			'username' => 'required|min:3|max:20',
			'password' => 'required|min:6|max:32',
		]);

		// Check if username is available
		$username = $request->input('username');
		if (Player::isUsernameAvailable($username)) {
			$player = Player::create($request->all());
			$player->password = Hash::make($request->input('password'));
			if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
				/* upload picture */
				$fileName = time().'.'.$request->photo->extension();
				$destinationPath = base_path('public/uploads');
				$request->file('photo')->move($destinationPath, $fileName);
				$player->photo = $request->root()."/uploads/".$fileName;
			} else {
				/* default picture */
				$player->photo = $request->root()."/uploads/user_default.png";
			}
			$player->role = "player";
			$player->save();
			return response()->json($player, 200);
		} else {
			return response()->json([
				'status' => 'fail', 
				'message' => "Ce nom d'utilisateur existe déjà."
			], 409);
		}
	}
	
	/* Update a player by id */
	public function updatePlayer(Request $request, $id) {
		$player = Player::find($id);

		// Check if the player updates his own profile
		if ($player != Auth::user()) {
			return response()->json(['status' => 'fail', 'message' => "Vous n'avez pas les droits pour effectuer cette modification."], 401);
		}

		$this->validate($request, [
			'photo' => 'image|mimes:jpeg,jpg,png,gif,svg|max:2048',
		]);

		/* Update informations */

		// Check if username is available
		$username = $request->input('username');
		if (Player::isUsernameAvailable($username)) {
			if ($request->input('username')) 
				$player->username = $request->input('username');
		}
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

		if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
			/* upload picture */
			$fileName = time().'.'.$request->photo->extension();
			$destinationPath = base_path('public/uploads');
			$request->file('photo')->move($destinationPath, $fileName);
			$player->photo = $request->root()."/uploads/".$fileName;
		}

		// Saving the player
		$player->save();

		// Return the modified player
		return response()->json($player, 200);
	}

	/* Delete Player */
	public function deletePlayer($id) {
		$player = Player::find($id);

		// Check if the player deletes his own profile or if he is admin
		if ($player != Auth::user() && Auth::user()->role != "admin") {
			return response()->json(['status' => 'fail', 'message' => "Vous n'avez pas les droits pour supprimer cet utilisateur."], 401);
		}

		$player->delete();
		return response()->json(['status' => 'success', 'message' => "Le profil a bien été supprimé."], 200);
	}

	/* Return the connected player */
	public function info() {
		return response()->json(Auth::user(), 200);
	}

	/* Return the teams for the player with id = $id */
	public function getTeamsByPlayer($id) {
		$player = Player::find($id);
		$teams = $player->teams;
		return response()->json($teams, 200);
	}

	public function getMatchesByPlayer($id) {
		$matches = Match::join('match_team', 'matches.id', '=', 'match_team.match_id')
			->join('teams', 'match_team.team_id', '=', 'teams.id')
			->join('team_player', 'teams.id', '=', 'team_player.team_id')
			->join('players', 'team_player.player_id', '=', 'players.id')
			->where('players.id', $id)->get();
		return response()->json($matches, 200);
	}
}