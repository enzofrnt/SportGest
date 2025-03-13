export interface User {
  id: number;
  email: string;
  firstname: string;
  lastname: string;
  roles: string[];
  createdAt: Date;
  updatedAt: Date;
}

export interface Athlete extends User {
  level: 'débutant' | 'intermédiaire' | 'avancé';
  preferences: {
    favoriteThemes: string[];
    favoriteCoaches: number[];
  };
  notifications: {
    email: boolean;
    app: boolean;
  };
}

export interface Coach extends User {
  specialities: string[];
  experience: number; // en années
  hourlyRate: number;
  availability: {
    day: string;
    startTime: string;
    endTime: string;
  }[];
} 