import { createAsyncThunk, createSlice } from "@reduxjs/toolkit"
import { apiClient } from "@/api/client"

const savedToken = localStorage.getItem("minipos_token")

const initialState = {
  user: null,
  token: savedToken,
  loading: false,
  error: null,
}

export const login = createAsyncThunk(
  "auth/login",
  async (credentials, { rejectWithValue }) => {
    try {
      const response = await apiClient("/auth/login", {
        method: "POST",
        body: JSON.stringify(credentials),
      })
      localStorage.setItem("minipos_token", response.token)
      return response
    } catch (error) {
      return rejectWithValue(error instanceof Error ? error.message : "Login failed")
    }
  },
)

export const loadUser = createAsyncThunk(
  "auth/loadUser",
  async (_, { rejectWithValue }) => {
    try {
      return await apiClient("/auth/me")
    } catch (error) {
      localStorage.removeItem("minipos_token")
      return rejectWithValue(error instanceof Error ? error.message : "Session expired")
    }
  },
)

export const logout = createAsyncThunk("auth/logout", async () => {
  try {
    await apiClient("/auth/logout", { method: "POST" })
  } finally {
    localStorage.removeItem("minipos_token")
  }
})

const authSlice = createSlice({
  name: "auth",
  initialState,
  reducers: {
    clearAuthError: (state) => { state.error = null },
    sessionExpired: (state) => { state.user = null; state.token = null; state.loading = false },
  },
  extraReducers: (builder) => {
    builder
      .addCase(login.pending, (state) => { state.loading = true; state.error = null })
      .addCase(login.fulfilled, (state, action) => { state.loading = false; state.user = action.payload.user; state.token = action.payload.token })
      .addCase(login.rejected, (state, action) => { state.loading = false; state.error = action.payload ?? "Login failed" })
      .addCase(loadUser.pending, (state) => { state.loading = true })
      .addCase(loadUser.fulfilled, (state, action) => { state.loading = false; state.user = action.payload })
      .addCase(loadUser.rejected, (state) => { state.loading = false; state.user = null; state.token = null })
      .addCase(logout.fulfilled, (state) => { state.user = null; state.token = null; state.error = null })
      .addCase(logout.rejected, (state) => { state.user = null; state.token = null; state.error = null })
  },
})

export const { clearAuthError, sessionExpired } = authSlice.actions
export default authSlice.reducer
