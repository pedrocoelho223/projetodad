export const SUITS = ["H","D","C","S"];
export const RANKS = ["A","7","K","J","Q","6","5","4","3","2"];
export const POINTS = { A:11, "7":10, K:4, J:3, Q:2 };
export const ORDER  = { "2":1,"3":2,"4":3,"5":4,"6":5,Q:6,J:7,K:8,"7":9,A:10 };

export function newDeck() {
  const d = [];
  for (const s of SUITS) for (const r of RANKS) d.push(`${r}${s}`);
  d.sort(() => Math.random() - 0.5);
  return d;
}

export function suit(c){ return c.slice(-1); }
export function rank(c){ return c.slice(0, -1); }
export function points(c){ return POINTS[rank(c)] ?? 0; }

export function trickWinner(lead, reply, trumpSuit) {
  const ls = suit(lead), rs = suit(reply);
  if (rs === trumpSuit && ls !== trumpSuit) return "REPLY";
  if (rs === trumpSuit && ls === trumpSuit) return ORDER[rank(reply)] > ORDER[rank(lead)] ? "REPLY" : "LEAD";
  if (rs === ls) return ORDER[rank(reply)] > ORDER[rank(lead)] ? "REPLY" : "LEAD";
  return "LEAD";
}

// Marks (para match)
export function marksFromWinPoints(winPoints) {
  if (winPoints === 120) return 4;  // bandeira
  if (winPoints >= 91)  return 2;  // capote
  if (winPoints >= 61)  return 1;  // moca/risca
  return 0;
}
