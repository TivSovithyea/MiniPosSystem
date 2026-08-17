import { createSlice } from "@reduxjs/toolkit"

const initialState = {
  items: [
    { id: 1, name: "Iced Americano", price: 3.5, quantity: 2 },
    { id: 2, name: "Butter Croissant", price: 2.75, quantity: 1 },
  ],
}

const cartSlice = createSlice({
  name: "cart",
  initialState,
  reducers: {
    addItem: (state, action) => {
      const item = state.items.find((entry) => entry.id === action.payload.id)
      if (item) item.quantity += 1
      else state.items.push({ ...action.payload, quantity: 1 })
    },
    removeItem: (state, action) => {
      state.items = state.items.filter((item) => item.id !== action.payload)
    },
    clearCart: (state) => { state.items = [] },
  },
})

export const { addItem, removeItem, clearCart } = cartSlice.actions
export default cartSlice.reducer
