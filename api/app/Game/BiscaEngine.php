<?php

namespace App\Game;

class BiscaEngine
{
    public $deck = [];
    public $playerHand = [];
    public $botHand = [];
    public $trumpCard = null;

    // Mesa: pode ter 'player' e/ou 'bot'
    public $tableCards = [];

    public $scores = ['player' => 0, 'bot' => 0];
    public $deckOpen = true;

    // 'player' ou 'bot' = quem tem a vez
    public $turn = 'player';

    // REGRAS OFICIAIS (40 Cartas)
    const VALUES = [
        'A' => 11, '7' => 10, 'K' => 4, 'J' => 3, 'Q' => 2,
        '6' => 0, '5' => 0, '4' => 0, '3' => 0, '2' => 0
    ];

    // Hierarquia de força (do mais forte para o mais fraco)
    const RANKS = ['A', '7', 'K', 'J', 'Q', '6', '5', '4', '3', '2'];
    const SUITS = ['S', 'H', 'D', 'C']; // Espadas, Copas, Ouros, Paus

    public function __construct($deck = null)
    {
        if ($deck) {
            foreach ($deck as $key => $value) {
                $this->$key = $value;
            }
        } else {
            $this->initGame();
        }
    }

    private function initGame()
    {
        // 1) Criar baralho
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $this->deck[] = [
                    'code' => $rank . $suit,
                    'rank' => $rank,
                    'suit' => $suit,
                    'value' => self::VALUES[$rank]
                ];
            }
        }
        shuffle($this->deck);

        // 2) Definir trunfo (última carta)
        $this->trumpCard = array_pop($this->deck);

        // 3) Dar 3 cartas a cada (Bisca 3)
        for ($i = 0; $i < 3; $i++) {
            $this->playerHand[] = array_pop($this->deck);
            $this->botHand[] = array_pop($this->deck);
        }

        // Colocar trunfo no fundo (virtualmente)
        array_unshift($this->deck, $this->trumpCard);
    }

    /**
     * Jogada do jogador.
     * - Se o bot já jogou (bot foi primeiro), então esta jogada fecha a ronda e resolve.
     * - Se o jogador for primeiro, chama o bot para responder e depois resolve.
     */
    public function playerMove($cardIndex)
    {
        if ($this->turn !== 'player') return false;
        if (!isset($this->playerHand[$cardIndex])) return false;

        $card = array_splice($this->playerHand, $cardIndex, 1)[0];
        $this->tableCards['player'] = $card;

        // Se o bot já tinha jogado (bot iniciou a ronda), agora temos as 2 cartas -> resolver
        if (isset($this->tableCards['bot'])) {
            $this->resolveTrick();
            return true;
        }

        // Caso normal: jogador iniciou -> bot responde
        $this->turn = 'bot';
        $this->botMove();

        return true;
    }

    /**
     * Jogada do bot:
     * - Se existe carta do player na mesa: bot está a responder -> joga e resolve.
     * - Se não existe carta do player: bot está a iniciar -> joga UMA carta e passa a vez ao player.
     */
    public function botMove()
    {
        if ($this->turn !== 'bot') return false;
        if (count($this->botHand) === 0) return false;

        // BOT A RESPONDER (player já jogou)
        if (isset($this->tableCards['player'])) {
            $opponentCard = $this->tableCards['player'];

            // Ordena mão do bot por "fraqueza" (primeiro as mais fracas)
            usort($this->botHand, fn($a, $b) => $a['value'] <=> $b['value']);

            $bestWinIndex = -1;

            foreach ($this->botHand as $index => $card) {
                if ($this->beats($card, $opponentCard)) {
                    $bestWinIndex = $index; // a mais fraca que ganha
                    break;
                }
            }

            $botCardIndex = ($bestWinIndex !== -1) ? $bestWinIndex : 0;

            $card = array_splice($this->botHand, $botCardIndex, 1)[0];
            $this->tableCards['bot'] = $card;

            // Agora sim: temos as duas -> resolve
            $this->resolveTrick();
            return true;
        }

        // BOT A INICIAR (não há carta do player na mesa)
        usort($this->botHand, fn($a, $b) => $a['value'] <=> $b['value']);
        $card = array_splice($this->botHand, 0, 1)[0];
        $this->tableCards['bot'] = $card;

        // Importante: NÃO resolver aqui (falta a carta do player)
        $this->turn = 'player';
        return true;
    }

    private function beats($cardA, $cardB)
    {
        $trumpSuit = $this->trumpCard['suit'];

        // Trunfo bate não-trunfo
        if ($cardA['suit'] === $trumpSuit && $cardB['suit'] !== $trumpSuit) return true;
        if ($cardB['suit'] === $trumpSuit && $cardA['suit'] !== $trumpSuit) return false;

        // Mesmo naipe: compara valor, depois hierarquia
        if ($cardA['suit'] === $cardB['suit']) {
            if ($cardA['value'] !== $cardB['value']) return $cardA['value'] > $cardB['value'];
            return array_search($cardA['rank'], self::RANKS) < array_search($cardB['rank'], self::RANKS);
        }

        // Naipe diferente sem trunfo: ganha quem jogou primeiro (logo, A só ganha se for trunfo, aqui não é)
        return false;
    }

    private function resolveTrick()
    {
        // Guard: só resolve se existirem as 2 cartas
        if (!isset($this->tableCards['player'], $this->tableCards['bot'])) {
            return;
        }

        $pCard = $this->tableCards['player'];
        $bCard = $this->tableCards['bot'];
        $points = ($pCard['value'] ?? 0) + ($bCard['value'] ?? 0);

        // Quem jogou primeiro?
        $botPlayedFirst = isset($this->tableCards['_bot_first']) ? (bool)$this->tableCards['_bot_first'] : false;

        // Melhor: inferir pelo estado antes de limpar:
        // Se o bot iniciou a ronda, então no momento em que o player jogou, já existia 'bot'.
        // Como neste resolveTrick já temos os 2, a forma segura é:
        // Se a vez atual era do bot e ele respondeu, jogador foi primeiro.
        // Se a vez atual era do player e ele respondeu, bot foi primeiro.
        // -> mas como aqui a vez pode já ter sido alterada, fazemos isto:
        // Se a carta do bot estava na mesa antes do player jogar, o playerMove resolve diretamente.
        // Para não depender disso, fazemos simples:
        // Se o bot NÃO tinha carta quando o player jogou, então player foi primeiro.
        // Isto já está garantido pelo fluxo:
        // - Player inicia: turn muda para bot e botMove responde e resolve.
        // - Bot inicia: botMove joga 1 carta e muda turn para player; playerMove joga e resolve.
        // Logo: se chegamos aqui e o turn é 'bot', o bot respondeu (player foi primeiro).
        // Se o turn é 'player', o player respondeu (bot foi primeiro).

        $playerWasFirst = ($this->turn === 'bot'); // bot respondeu -> player foi primeiro

        if ($playerWasFirst) {
            // bot jogou em 2º
            $botWins = $this->beats($bCard, $pCard);
        } else {
            // bot jogou em 1º, player respondeu
            $botWins = !$this->beats($pCard, $bCard);
        }

        $winner = $botWins ? 'bot' : 'player';
        $this->scores[$winner] += $points;

        // Limpar mesa
        $this->tableCards = [];

        // Pescar cartas (se houver baralho)
        if (count($this->deck) > 0) {
            // vencedor pesca primeiro
            if ($winner === 'player') {
                $draw1 = array_pop($this->deck);
                $draw2 = array_pop($this->deck);

                if ($draw1) $this->playerHand[] = $draw1;
                if ($draw2) $this->botHand[] = $draw2;
            } else {
                $draw1 = array_pop($this->deck);
                $draw2 = array_pop($this->deck);

                if ($draw1) $this->botHand[] = $draw1;
                if ($draw2) $this->playerHand[] = $draw2;
            }
        } else {
            $this->deckOpen = false;
        }

        // Próximo turno é do vencedor
        $this->turn = $winner;

        // Se o bot ganhou, ele inicia a próxima ronda automaticamente (joga 1 carta e espera)
        if ($winner === 'bot' && count($this->botHand) > 0) {
            $this->botMove();
        }
    }

    public function getState()
{
    $gameOver = count($this->playerHand) === 0
             && count($this->botHand) === 0;

    return [
        'playerHand' => $this->playerHand,
        'botHand' => $this->botHand,
        'trumpCard' => $this->trumpCard,
        'deckCount' => count($this->deck),
        'table' => $this->tableCards,
        'scores' => $this->scores,
        'turn' => $this->turn,
        'gameOver' => $gameOver,
        'winner' => $gameOver
            ? ($this->scores['player'] > $this->scores['bot'] ? 'player' : 'bot')
            : null
    ];
}

}
