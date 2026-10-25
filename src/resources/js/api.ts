import axios from 'axios';

export const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export function getApiErrorMessage(error: unknown, fallback: string): string {
    if (axios.isAxiosError<{ message?: string }>(error)) {
        return error.response?.data.message ?? fallback;
    }

    if (error instanceof Error) {
        return error.message;
    }

    return fallback;
}
