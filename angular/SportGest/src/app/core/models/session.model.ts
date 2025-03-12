export interface Session {
  id: number;
  date: Date;
  startTime: string;
  endTime: string;
  coach: number; // ID du coach
  type: 'fitness' | 'cardio' | 'musculation' | 'crossfit';
  format: 'solo' | 'duo' | 'trio';
  level: 'débutant' | 'intermédiaire' | 'avancé';
  status: 'prévue' | 'validée' | 'annulée';
  exercises: Exercise[];
  participants: number[]; // IDs des participants
  maxParticipants: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface Exercise {
  id: number;
  name: string;
  description: string;
  duration: number; // en minutes
  difficulty: 'facile' | 'moyen' | 'difficile';
} 