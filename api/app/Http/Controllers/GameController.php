<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
{
    // 1. CORREÇÃO: Carregar logo todas as relações (players e winner)
    // Isto permite ao Frontend mostrar "Agustina Hintz" em vez do ID 29
    $query = Game::with([
    'player1' => function($query) { $query->withTrashed(); },
    'player2' => function($query) { $query->withTrashed(); },
    'winner'  => function($query) { $query->withTrashed(); }
]);

    // Filtros
    if ($request->has('type') && in_array($request->type, ['3', '9'])) {
        $query->where('type', $request->type);
    }

    if ($request->has('status') && in_array($request->status, ['Pending', 'Playing', 'Ended', 'Interrupted'])) {
        $query->where('status', $request->status);
    }

    // Sorting
    $sortField = $request->input('sort_by', 'began_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    $allowedSortFields = [
        'id',          // Adicionei ID
        'began_at',
        'created_at',  // Adicionei created_at para prevenir erros
        'ended_at',
        'total_time',
        'type',
        'status'
    ];

    if (in_array($sortField, $allowedSortFields)) {
        $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
    }

    // Pagination
    $perPage = $request->input('per_page', 15);
    $games = $query->paginate($perPage);

    // Retorno JSON formatado para o Vue
    return response()->json([
        'data' => $games->items(),
        'meta' => [
            'current_page' => $games->currentPage(),
            'last_page' => $games->lastPage(),
            'per_page' => $games->perPage(),
            'total' => $games->total()
        ]
    ]);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'type' => 'required|in:3,9',
    ]);

    $user = Auth::user();
    if (!$user) {
    return response()->json(['message' => 'Unauthenticated'], 401);

}


    // 1. Criar baralho (40 cartas)
    $suits = ['H', 'D', 'C', 'S'];
    $ranks = ['A','7','K','J','Q','6','5','4','3','2'];
    $deck = [];

    foreach ($suits as $suit) {
        foreach ($ranks as $rank) {
            $deck[] = $rank . $suit;
        }
    }

    shuffle($deck);

    // 2. Trunfo = última carta
    $trumpCard = end($deck);
    $trumpSuit = substr($trumpCard, -1);

    // 3. Dar cartas iniciais
    $playerHand = array_splice($deck, 0, 3);
    $botHand    = array_splice($deck, 0, 3);

    // 4. Estado inicial do jogo (custom)
    $gameState = [
        'mode' => 'single',
        'trump' => $trumpSuit,
        'stock' => array_values($deck),
        'hands' => [
            'player' => $playerHand,
            'bot' => $botHand,
        ],
        'trick' => [
            'lead' => 'player',
            'cards' => []
        ],
        'won' => [
            'player' => [],
            'bot' => []
        ],
        'phase' => 'stock',
        'turn' => 'player',
        'timer_started_at' => null
    ];

    // 5. Criar jogo
    $game = Game::create([
        'type' => $request->type,
        'status' => 'Playing',
        'player1_user_id' => $user->id,
        'player2_user_id' => null,
        'began_at' => now(),
        'custom' => $gameState
    ]);

    return response()->json($game, 201);
}


    /**
     * Display the specified resource.
     */
    public function show(Game $game)
{
    return response()->json($game);
}


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        //
    }

    public function play(Request $request, Game $game)
{
    $request->validate([
        'card' => 'required|string',
    ]);

    // Segurança: só quem está no jogo pode jogar
    $user = Auth::user();
    if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

    if ($game->player1_user_id !== $user->id) {
        return response()->json(['message' => 'Not your game'], 403);
    }

    if ($game->status !== 'Playing') {
        return response()->json(['message' => 'Game is not in Playing state'], 400);
    }

    $state = $game->custom ?? [];
    if (($state['mode'] ?? null) !== 'single') {
        return response()->json(['message' => 'Not a single-player game'], 400);
    }

    // Helpers
    $rankOrder = ['2'=>1,'3'=>2,'4'=>3,'5'=>4,'6'=>5,'Q'=>6,'J'=>7,'K'=>8,'7'=>9,'A'=>10];
    $pointsMap = ['A'=>11,'7'=>10,'K'=>4,'J'=>3,'Q'=>2];

    $getSuit = fn($card) => substr($card, -1);
    $getRank = fn($card) => substr($card, 0, -1);
    $cardPoints = function($card) use ($getRank, $pointsMap) {
        $r = $getRank($card);
        return $pointsMap[$r] ?? 0;
    };

    $hasStock = fn() => !empty($state['stock']);

    $canFollowSuit = function($hand, $suit) use ($getSuit) {
        foreach ($hand as $c) if ($getSuit($c) === $suit) return true;
        return false;
    };

    $isCardInHand = function($hand, $card) {
        return in_array($card, $hand, true);
    };

    $removeCardFromHand = function(&$hand, $card) {
        $idx = array_search($card, $hand, true);
        if ($idx === false) return false;
        array_splice($hand, $idx, 1);
        return true;
    };

    $winnerOfTrick = function($leadCard, $replyCard, $trumpSuit) use ($getSuit, $getRank, $rankOrder) {
        $leadSuit = $getSuit($leadCard);
        $replySuit = $getSuit($replyCard);

        // Se reply é trunfo e lead não é trunfo -> reply ganha
        if ($replySuit === $trumpSuit && $leadSuit !== $trumpSuit) return 'reply';
        // Se ambos são trunfo -> maior rank ganha
        if ($replySuit === $trumpSuit && $leadSuit === $trumpSuit) {
            return ($rankOrder[$getRank($replyCard)] > $rankOrder[$getRank($leadCard)]) ? 'reply' : 'lead';
        }

        // Se mesmo naipe -> maior rank ganha
        if ($replySuit === $leadSuit) {
            return ($rankOrder[$getRank($replyCard)] > $rankOrder[$getRank($leadCard)]) ? 'reply' : 'lead';
        }

        // Naipe diferente, sem trunfo -> lead ganha
        return 'lead';
    };

    $drawCard = function(&$stock) {
        if (empty($stock)) return null;
        return array_shift($stock);
    };

    // Normalizar estruturas
    $state['hands']['player'] = $state['hands']['player'] ?? [];
    $state['hands']['bot'] = $state['hands']['bot'] ?? [];
    $state['won']['player'] = $state['won']['player'] ?? [];
    $state['won']['bot'] = $state['won']['bot'] ?? [];
    $state['trick']['cards'] = $state['trick']['cards'] ?? [];
    $state['turn'] = $state['turn'] ?? 'player';
    $state['phase'] = $state['phase'] ?? (!empty($state['stock']) ? 'stock' : 'final');

    // 1) Validar turno
    if ($state['turn'] !== 'player') {
        return response()->json(['message' => 'Not your turn'], 400);
    }

    $playedCard = $request->card;

    // 2) Validar que a carta está na mão
    if (!$isCardInHand($state['hands']['player'], $playedCard)) {
        return response()->json(['message' => 'Card not in hand'], 400);
    }

    // 3) Validar regra de assistir (SÓ quando stock acabou = phase final)
    $cardsOnTable = $state['trick']['cards'];
    $isSecond = count($cardsOnTable) === 1;

    if ($isSecond && ($state['phase'] === 'final')) {
        $leadSuit = $getSuit($cardsOnTable[0]['card']);
        if ($canFollowSuit($state['hands']['player'], $leadSuit) && $getSuit($playedCard) !== $leadSuit) {
            return response()->json(['message' => 'Must follow suit in final phase'], 400);
        }
    }

    // 4) Aplicar jogada do player
    $removeCardFromHand($state['hands']['player'], $playedCard);
    $state['trick']['cards'][] = ['by' => 'player', 'card' => $playedCard];

    // Se player foi o primeiro a jogar, lead = player
    if (count($state['trick']['cards']) === 1) {
        $state['trick']['lead'] = 'player';
    }

    // 5) BOT joga (no mesmo request) se for preciso
    if (count($state['trick']['cards']) === 1) {
        // Bot é segundo
        $leadSuit = $getSuit($state['trick']['cards'][0]['card']);
        $valid = $state['hands']['bot'];

        // Se final phase e bot pode assistir, filtra
        if ($state['phase'] === 'final' && $canFollowSuit($valid, $leadSuit)) {
            $valid = array_values(array_filter($valid, fn($c) => $getSuit($c) === $leadSuit));
        }

        // Bot “tenta ganhar”: escolhe uma que ganhe; se não, menor valor por pontos e depois rank
        $leadCard = $state['trick']['cards'][0]['card'];
        $trumpSuit = $state['trump'];

        $winning = [];
        foreach ($valid as $c) {
            $w = $winnerOfTrick($leadCard, $c, $trumpSuit);
            if ($w === 'reply') $winning[] = $c;
        }

        $chooseLowest = function($cards) use ($cardPoints, $getRank, $rankOrder) {
            usort($cards, function($a, $b) use ($cardPoints, $getRank, $rankOrder) {
                $pa = $cardPoints($a); $pb = $cardPoints($b);
                if ($pa !== $pb) return $pa <=> $pb; // menos pontos primeiro
                return $rankOrder[$getRank($a)] <=> $rankOrder[$getRank($b)];
            });
            return $cards[0];
        };

        $botCard = !empty($winning) ? $chooseLowest($winning) : $chooseLowest($valid);

        $removeCardFromHand($state['hands']['bot'], $botCard);
        $state['trick']['cards'][] = ['by' => 'bot', 'card' => $botCard];
    }

    // 6) Resolver vaza (quando houver 2 cartas)
    if (count($state['trick']['cards']) === 2) {
        $leadBy = $state['trick']['lead']; // 'player' ou 'bot'
        $leadCard = $state['trick']['cards'][0]['card'];
        $replyCard = $state['trick']['cards'][1]['card'];

        // Determinar vencedor
        $w = $winnerOfTrick($leadCard, $replyCard, $state['trump']);
        // 'lead' ganha -> vencedor é leadBy; 'reply' ganha -> vencedor é o outro
        $winner = ($w === 'lead') ? $leadBy : (($leadBy === 'player') ? 'bot' : 'player');

        // Guardar cartas ganhas
        $state['won'][$winner][] = $leadCard;
        $state['won'][$winner][] = $replyCard;

        // Limpar mesa
        $state['trick']['cards'] = [];
        $state['trick']['lead'] = $winner;

        // Compras (se houver stock)
        if (!empty($state['stock'])) {
            $first = $winner;
            $second = ($winner === 'player') ? 'bot' : 'player';

            $c1 = $drawCard($state['stock']);
            if ($c1) $state['hands'][$first][] = $c1;

            $c2 = $drawCard($state['stock']);
            if ($c2) $state['hands'][$second][] = $c2;
        }

        // Atualizar fase
        $state['phase'] = (!empty($state['stock'])) ? 'stock' : 'final';

        // Próximo turno = vencedor da vaza (joga primeiro)
        $state['turn'] = $winner;
    } else {
        // Se por algum motivo não resolveu, passa turno para bot (não deve acontecer aqui)
        $state['turn'] = 'bot';
    }

    // 7) Se acabou (stock vazio e mãos vazias)
    $handsEmpty = empty($state['hands']['player']) && empty($state['hands']['bot']) && empty($state['trick']['cards']);
    if ($handsEmpty && empty($state['stock'])) {
        $p1 = 0; $p2 = 0;
        foreach ($state['won']['player'] as $c) $p1 += $cardPoints($c);
        foreach ($state['won']['bot'] as $c) $p2 += $cardPoints($c);

        // Guardar pontos no Game
        $game->player1_points = $p1;
        $game->player2_points = $p2;

        if ($p1 > $p2) {
            $game->winner_user_id = $game->player1_user_id;
        } elseif ($p2 > $p1) {
            $game->winner_user_id = null; // bot (não há user)
        } else {
            $game->winner_user_id = null; // empate raro
        }

        $game->status = 'Ended';
        $game->ended_at = now();
    }

    $game->custom = $state;
    $game->save();

    return response()->json($game);
}

}
